<?php
/**
 * Class Reply
 *
 * Domain model for replies to posts (WBS 3.4.1/3.4.2).
 * A reply belongs to one post and one author, is at most 280 characters
 * like a post, and is soft-deleted like a post (deleted_at flag).
 * Threading is one level deep: replies render chronologically under
 * their parent post on the post detail page.
 */
require_once __DIR__ . '/Cache.php';
require_once __DIR__ . '/Post.php';

class Reply {
    private PDO $db;

    public const MAX_LENGTH = 280;
    public const MIN_LENGTH = 1;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    public function validateContent(string $content): array {
        $clean = trim($content);
        $length = mb_strlen($clean, 'UTF-8');

        if ($length < self::MIN_LENGTH) {
            return [
                'valid' => false,
                'error' => 'Reply cannot be empty or contain only whitespace.',
                'clean' => ''
            ];
        }

        if ($length > self::MAX_LENGTH) {
            return [
                'valid' => false,
                'error' => 'Reply cannot exceed ' . self::MAX_LENGTH . ' characters (currently ' . $length . ').',
                'clean' => $clean
            ];
        }

        return ['valid' => true, 'error' => null, 'clean' => $clean];
    }

    /**
     * Create a reply. The parent post must exist and not be soft-deleted.
     * @return int|false The new reply ID, or false on validation/db failure
     */
    public function create(int $userId, int $postId, string $content) {
        if ($userId <= 0 || $postId <= 0) {
            return false;
        }

        $validation = $this->validateContent($content);
        if (!$validation['valid']) {
            return false;
        }

        // Parent post must exist and be visible.
        $checkSql = "SELECT id FROM posts WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindParam(':id', $postId, PDO::PARAM_INT);
        $checkStmt->execute();
        if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $sql = "INSERT INTO replies (post_id, user_id, content, created_at, updated_at)
                VALUES (:post_id, :user_id, :content, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':content', $validation['clean'], PDO::PARAM_STR);

        try {
            if ($stmt->execute()) {
                $newReplyId = (int)$this->db->lastInsertId();

                // Reply counts are rendered inside cached feed rows:
                // invalidate cached feeds (Sprint 4, WBS 4.3.5 hooks).
                Cache::getInstance()->deleteByPrefix('feed_');

                // Sprint 5 (WBS 5.x): Notify post author if not replying to own post
                try {
                    require_once __DIR__ . '/Notification.php';
                    $postAuthorStmt = $this->db->prepare("SELECT user_id FROM posts WHERE id = :id LIMIT 1");
                    $postAuthorStmt->bindParam(':id', $postId, PDO::PARAM_INT);
                    $postAuthorStmt->execute();
                    $postAuthorId = (int)$postAuthorStmt->fetchColumn();

                    if ($postAuthorId > 0 && $postAuthorId !== $userId) {
                        $uStmt = $this->db->prepare("SELECT name FROM users WHERE id = :id LIMIT 1");
                        $uStmt->bindParam(':id', $userId, PDO::PARAM_INT);
                        $uStmt->execute();
                        $replierName = $uStmt->fetchColumn() ?: 'Someone';

                        $notif = new Notification($this->db);
                        $notif->create($postAuthorId, Notification::TYPE_REPLY, $postId, "{$replierName} replied to your post.", $userId);
                    }
                } catch (Throwable $t) {
                    error_log("Reply notification error: " . $t->getMessage());
                }

                return $newReplyId;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Reply creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * All visible replies to a post, oldest first (thread order),
     * with author details.
     */
    public function getByPostId(int $postId, int $limit = 100, int $offset = 0): array {
        $sql = "SELECT r.id, r.post_id, r.user_id, r.content, r.created_at, r.updated_at,
                       u.name AS author_name, u.role AS author_role
                FROM replies r
                JOIN users u ON r.user_id = u.id
                WHERE r.post_id = :post_id AND r.deleted_at IS NULL
                ORDER BY r.created_at ASC, r.id ASC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Sprint 5 UI polish (N-7): replies rendered an absolute time
        // ("Nov 12, 2:16 PM") while posts use relative time — stamp the
        // same time_ago so list contexts agree.
        foreach ($rows as &$row) {
            $row['time_ago'] = Post::formatTimeAgo($row['created_at']);
        }
        return $rows;
    }

    /**
     * Soft-delete a reply. Only the author or an admin may delete it.
     */
    /**
     * @param bool $canDeleteAny Whether the caller holds the
     *        'reply.delete_any' permission (RBAC, Sprint 5 — the caller
     *        decides via Authorization, this method only enforces)
     */
    public function delete(int $replyId, int $userId, bool $canDeleteAny = false): bool {
        $checkSql = "SELECT user_id FROM replies WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindParam(':id', $replyId, PDO::PARAM_INT);
        $checkStmt->execute();
        $owner = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$owner) {
            return false;
        }

        $isAuthor = ((int)$owner['user_id'] === $userId);
        if (!$isAuthor && !$canDeleteAny) {
            return false;
        }

        $sql = "UPDATE replies SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $replyId, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok) {
            Cache::getInstance()->deleteByPrefix('feed_');
        }
        return $ok;
    }

    public function countByPost(int $postId): int {
        $sql = "SELECT COUNT(*) FROM replies WHERE post_id = :post_id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
