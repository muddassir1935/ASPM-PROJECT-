<?php
/**
 * Class Notification
 *
 * Domain model for User Notifications (Sprint 5, WBS 5.x).
 * Manages in-app alerts triggered by social actions (follows, replies, likes).
 * Supports real-time unread counts, pagination, and bulk/individual read marking.
 * Enforces suppression of self-action notifications.
 */
class Notification {
    private PDO $db;

    public const TYPE_FOLLOW = 'follow';
    public const TYPE_REPLY  = 'reply';
    public const TYPE_LIKE   = 'like';

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Create a notification.
     * Never generates a notification if the actor is the same as the recipient.
     *
     * @param int $userId Recipient user ID
     * @param string $type Notification type ('follow'|'reply'|'like')
     * @param int|null $referenceId Target entity ID (e.g., post ID or follower ID)
     * @param string $message Human-readable notification description
     * @param int|null $actorId User who performed the action
     * @return int|false The new notification ID or false on failure/suppression
     */
    public function create(int $userId, string $type, ?int $referenceId, string $message, ?int $actorId = null) {
        if ($userId <= 0 || trim($type) === '' || trim($message) === '') {
            return false;
        }

        // Suppress notifications for self-actions
        if ($actorId !== null && (int)$actorId === (int)$userId) {
            return false;
        }

        $cleanType = mb_substr(trim($type), 0, 32, 'UTF-8');
        $cleanMessage = mb_substr(trim($message), 0, 255, 'UTF-8');

        $sql = "INSERT INTO notifications (user_id, actor_id, type, reference_id, message, is_read, created_at)
                VALUES (:user_id, :actor_id, :type, :reference_id, :message, 0, CURRENT_TIMESTAMP)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            if ($actorId !== null && (int)$actorId > 0) {
                $stmt->bindValue(':actor_id', (int)$actorId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':actor_id', null, PDO::PARAM_NULL);
            }
            $stmt->bindParam(':type', $cleanType, PDO::PARAM_STR);
            if ($referenceId !== null) {
                $stmt->bindValue(':reference_id', (int)$referenceId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':reference_id', null, PDO::PARAM_NULL);
            }
            $stmt->bindParam(':message', $cleanMessage, PDO::PARAM_STR);

            if ($stmt->execute()) {
                return (int)$this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Notification creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get count of unread notifications for a user.
     */
    public function countUnread(int $userId): int {
        if ($userId <= 0) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * List unread notifications for a user.
     */
    public function listUnread(int $userId, int $limit = 50): array {
        if ($userId <= 0) {
            return [];
        }

        $sql = "SELECT n.id, n.user_id, n.actor_id, n.type, n.reference_id, n.message, n.is_read, n.created_at,
                       u.name AS actor_name, u.role AS actor_role
                FROM notifications n
                LEFT JOIN users u ON n.actor_id = u.id
                WHERE n.user_id = :user_id AND n.is_read = 0
                ORDER BY n.created_at DESC, n.id DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['time_ago'] = $this->formatTimeAgo($row['created_at']);
        }
        return $rows;
    }

    /**
     * List all notifications (read and unread) for a user with pagination.
     */
    public function listAll(int $userId, int $limit = 50, int $offset = 0): array {
        if ($userId <= 0) {
            return [];
        }

        $sql = "SELECT n.id, n.user_id, n.actor_id, n.type, n.reference_id, n.message, n.is_read, n.created_at,
                       u.name AS actor_name, u.role AS actor_role
                FROM notifications n
                LEFT JOIN users u ON n.actor_id = u.id
                WHERE n.user_id = :user_id
                ORDER BY n.created_at DESC, n.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['time_ago'] = $this->formatTimeAgo($row['created_at']);
        }
        return $rows;
    }

    /**
     * Mark a specific notification as read.
     */
    public function markRead(int $notificationId, int $userId): bool {
        if ($notificationId <= 0 || $userId <= 0) {
            return false;
        }

        $sql = "UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $notificationId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Mark all notifications for a user as read.
     */
    public function markAllRead(int $userId): bool {
        if ($userId <= 0) {
            return false;
        }

        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Delete a single notification.
     */
    public function delete(int $notificationId, int $userId): bool {
        if ($notificationId <= 0 || $userId <= 0) {
            return false;
        }

        $sql = "DELETE FROM notifications WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $notificationId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Relative human-readable time string helper.
     */
    public function formatTimeAgo(string $datetime): string {
        $timestamp = strtotime($datetime);
        if (!$timestamp) {
            return 'recently';
        }

        $diff = time() - $timestamp;

        if ($diff < 5) {
            return 'just now';
        }
        if ($diff < 60) {
            return $diff . 's ago';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm ago';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h ago';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . 'd ago';
        }
        return date('M j, Y', $timestamp);
    }
}
