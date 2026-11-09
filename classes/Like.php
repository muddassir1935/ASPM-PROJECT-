<?php
/**
 * Class Like
 *
 * Domain model for post likes / heart reactions (Sprint 4, WBS 4.1).
 * Supports idempotent add/remove, toggle, counts, and fetching liked posts.
 * Soft-deleted posts are never returned in like feeds.
 */
require_once __DIR__ . '/Cache.php';

class Like {
    private PDO $db;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Like a post (idempotent). Post must exist and not be soft-deleted.
     */
    public function add(int $userId, int $postId): bool {
        if ($userId <= 0 || $postId <= 0) {
            return false;
        }

        if ($this->isLiked($userId, $postId)) {
            return true;
        }

        $checkSql = "SELECT id FROM posts WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindParam(':id', $postId, PDO::PARAM_INT);
        $checkStmt->execute();
        if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $sql = "INSERT INTO likes (user_id, post_id, created_at)
                VALUES (:user_id, :post_id, CURRENT_TIMESTAMP)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);

        try {
            $ok = $stmt->execute();
            if ($ok) {
                // Keep a cached like counter (if one exists) in step.
                Cache::getInstance()->incrementCounter("like_count_{$postId}");
            }
            return $ok;
        } catch (PDOException $e) {
            error_log("Like add error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unlike a post (idempotent).
     */
    public function remove(int $userId, int $postId): bool {
        if ($userId <= 0 || $postId <= 0) {
            return false;
        }

        $sql = "DELETE FROM likes WHERE user_id = :user_id AND post_id = :post_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);

        try {
            $ok = $stmt->execute();
            if ($ok && $stmt->rowCount() > 0) {
                // Counter changed: drop the cached value so the next read
                // recomputes from the database (safer than a cached DECR).
                Cache::getInstance()->delete("like_count_{$postId}");
            }
            return $ok;
        } catch (PDOException $e) {
            error_log("Like remove error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle like state.
     * Returns ['liked' => bool, 'count' => int] or null on failure.
     */
    public function toggle(int $userId, int $postId): ?array {
        if ($userId <= 0 || $postId <= 0) {
            return null;
        }

        if ($this->isLiked($userId, $postId)) {
            if (!$this->remove($userId, $postId)) {
                return null;
            }
            return [
                'liked' => false,
                'count' => $this->countByPost($postId)
            ];
        }

        if (!$this->add($userId, $postId)) {
            return null;
        }

        return [
            'liked' => true,
            'count' => $this->countByPost($postId)
        ];
    }

    /**
     * Check if a user has liked a post.
     */
    public function isLiked(int $userId, int $postId): bool {
        if ($userId <= 0 || $postId <= 0) {
            return false;
        }

        $sql = "SELECT 1 FROM likes WHERE user_id = :user_id AND post_id = :post_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Total likes on a post.
     * Sprint 4 (WBS 4.3): cache-aside counter with a 5-minute TTL;
     * add() increments a warm counter, remove() invalidates it.
     */
    public function countByPost(int $postId): int {
        if ($postId <= 0) {
            return 0;
        }

        $cache = Cache::getInstance();
        $cached = $cache->getCounter("like_count_{$postId}");
        if ($cached !== null) {
            return $cached;
        }

        $sql = "SELECT COUNT(*) FROM likes WHERE post_id = :post_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $stmt->execute();

        $count = (int)$stmt->fetchColumn();
        $cache->setCounter("like_count_{$postId}", $count, 300);
        return $count;
    }

    /**
     * Total likes given by a user (on non-deleted posts).
     */
    public function countByUser(int $userId): int {
        if ($userId <= 0) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM likes l
                JOIN posts p ON l.post_id = p.id
                WHERE l.user_id = :user_id AND p.deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Get posts liked by a user (for the Profile Likes tab).
     * $viewerId is whose like state is_liked describes — it defaults to the
     * list owner, but a different signed-in viewer must see THEIR OWN state,
     * not the owner's (is_liked was previously hardcoded to 1, which showed
     * every visitor a filled heart on the owner's liked posts).
     */
    public function getLikedPosts(int $userId, int $limit = 50, int $offset = 0, int $viewerId = 0): array {
        if ($userId <= 0) {
            return [];
        }
        $effectiveViewer = $viewerId > 0 ? $viewerId : $userId;

        $sql = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                       u.name AS author_name, u.email AS author_email, u.role AS author_role,
                       (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id AND r.deleted_at IS NULL) AS reply_count,
                       (SELECT COUNT(*) FROM likes l2 WHERE l2.post_id = p.id) AS like_count,
                       (SELECT COUNT(*) FROM likes lv WHERE lv.post_id = p.id AND lv.user_id = :viewer_id) AS is_liked
                FROM likes l
                JOIN posts p ON l.post_id = p.id
                JOIN users u ON p.user_id = u.id
                WHERE l.user_id = :user_id AND p.deleted_at IS NULL
                ORDER BY l.created_at DESC, l.post_id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':viewer_id', $effectiveViewer, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get list of users who liked a post.
     */
    public function getUsersWhoLiked(int $postId, int $limit = 50): array {
        if ($postId <= 0) {
            return [];
        }

        $sql = "SELECT u.id, u.name, u.email, u.role, u.bio, u.cover_style, l.created_at AS liked_at
                FROM likes l
                JOIN users u ON l.user_id = u.id
                WHERE l.post_id = :post_id
                ORDER BY l.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

