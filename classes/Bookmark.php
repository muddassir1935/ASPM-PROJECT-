<?php
/**
 * Class Bookmark
 *
 * Domain model for saved posts (WBS 3.4.3/3.4.4).
 * A bookmark is one row per (user, post) pair; saving twice still
 * counts once. Soft-deleted posts never appear in a bookmark list.
 */
require_once __DIR__ . '/Cache.php';

class Bookmark {
    private PDO $db;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Save a post. Idempotent; the post must exist and be visible.
     */
    public function add(int $userId, int $postId): bool {
        if ($userId <= 0 || $postId <= 0) {
            return false;
        }

        if ($this->isBookmarked($userId, $postId)) {
            return true;
        }

        $checkSql = "SELECT id FROM posts WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindParam(':id', $postId, PDO::PARAM_INT);
        $checkStmt->execute();
        if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $sql = "INSERT INTO bookmarks (user_id, post_id, created_at)
                VALUES (:user_id, :post_id, CURRENT_TIMESTAMP)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);

        try {
            $ok = $stmt->execute();
            if ($ok) {
                // is_bookmarked is rendered inside cached feed rows:
                // invalidate cached feeds (Sprint 4, WBS 4.3.5 hooks).
                Cache::getInstance()->deleteByPrefix('feed_');
            }
            return $ok;
        } catch (PDOException $e) {
            error_log("Bookmark error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a saved post. Returns true even if it was not saved,
     * because the end state (not saved) is what the caller asked for.
     */
    public function remove(int $userId, int $postId): bool {
        if ($userId <= 0 || $postId <= 0) {
            return false;
        }

        $sql = "DELETE FROM bookmarks WHERE user_id = :user_id AND post_id = :post_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);

        try {
            $ok = $stmt->execute();
            if ($ok) {
                Cache::getInstance()->deleteByPrefix('feed_');
            }
            return $ok;
        } catch (PDOException $e) {
            error_log("Bookmark remove error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle a bookmark. Returns the new state (true = saved),
     * or null on an invalid request.
     */
    public function toggle(int $userId, int $postId): ?bool {
        if ($userId <= 0 || $postId <= 0) {
            return null;
        }

        if ($this->isBookmarked($userId, $postId)) {
            return $this->remove($userId, $postId) ? false : null;
        }
        return $this->add($userId, $postId) ? true : null;
    }

    public function isBookmarked(int $userId, int $postId): bool {
        if ($userId <= 0 || $postId <= 0) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM bookmarks WHERE user_id = :user_id AND post_id = :post_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Saved posts for a user, most recently saved first.
     * Soft-deleted posts are excluded even if still bookmarked.
     */
    public function getBookmarkedPosts(int $userId, int $limit = 50, int $offset = 0): array {
        $sql = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                       u.name AS author_name, u.email AS author_email, u.role AS author_role,
                       (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id AND r.deleted_at IS NULL) AS reply_count,
                       (SELECT COUNT(*) FROM likes l2 WHERE l2.post_id = p.id) AS like_count,
                       (SELECT COUNT(*) FROM likes lv WHERE lv.post_id = p.id AND lv.user_id = :viewer_id) AS is_liked
                FROM bookmarks b
                JOIN posts p ON p.id = b.post_id
                JOIN users u ON p.user_id = u.id
                WHERE b.user_id = :user_id AND p.deleted_at IS NULL
                ORDER BY b.created_at DESC, b.post_id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':viewer_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByUser(int $userId): int {
        $sql = "SELECT COUNT(*) FROM bookmarks b
                JOIN posts p ON p.id = b.post_id
                WHERE b.user_id = :user_id AND p.deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
