<?php
/**
 * Class Follow
 *
 * Domain model for the Sprint 3 social graph (WBS 3.2).
 * A follow is a one-way relationship: follower_id follows followed_id.
 * Uses PDO prepared statements throughout; the (follower_id, followed_id)
 * pair is unique so a follow is idempotent.
 */
require_once __DIR__ . '/Cache.php';

class Follow {
    private PDO $db;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Follow a user. Idempotent: following someone twice still counts once.
     * Returns false for invalid ids or self-follows.
     */
    public function follow(int $followerId, int $followedId): bool {
        if ($followerId <= 0 || $followedId <= 0 || $followerId === $followedId) {
            return false;
        }

        if ($this->isFollowing($followerId, $followedId)) {
            return true;
        }

        $sql = "INSERT INTO follows (follower_id, followed_id, created_at)
                VALUES (:follower_id, :followed_id, CURRENT_TIMESTAMP)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':follower_id', $followerId, PDO::PARAM_INT);
        $stmt->bindParam(':followed_id', $followedId, PDO::PARAM_INT);

        try {
            $ok = $stmt->execute();
            if ($ok) {
                // The follower's personalized feed membership changes:
                // invalidate cached feeds (Sprint 4, WBS 4.3.5 hooks).
                Cache::getInstance()->deleteByPrefix('feed_');
            }
            return $ok;
        } catch (PDOException $e) {
            // Duplicate pair raced in, or a foreign key failed (unknown user).
            error_log("Follow error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unfollow a user. Returns true even if the follow did not exist,
     * because the end state (not following) is what the caller asked for.
     */
    public function unfollow(int $followerId, int $followedId): bool {
        if ($followerId <= 0 || $followedId <= 0) {
            return false;
        }

        $sql = "DELETE FROM follows WHERE follower_id = :follower_id AND followed_id = :followed_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':follower_id', $followerId, PDO::PARAM_INT);
        $stmt->bindParam(':followed_id', $followedId, PDO::PARAM_INT);

        try {
            $ok = $stmt->execute();
            if ($ok) {
                Cache::getInstance()->deleteByPrefix('feed_');
            }
            return $ok;
        } catch (PDOException $e) {
            error_log("Unfollow error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle a follow. Returns the new state: true = now following,
     * false = now not following, null = invalid request (self/invalid id).
     */
    public function toggle(int $followerId, int $followedId): ?bool {
        if ($followerId <= 0 || $followedId <= 0 || $followerId === $followedId) {
            return null;
        }

        if ($this->isFollowing($followerId, $followedId)) {
            return $this->unfollow($followerId, $followedId) ? false : null;
        }
        return $this->follow($followerId, $followedId) ? true : null;
    }

    public function isFollowing(int $followerId, int $followedId): bool {
        if ($followerId <= 0 || $followedId <= 0) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM follows
                WHERE follower_id = :follower_id AND followed_id = :followed_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':follower_id', $followerId, PDO::PARAM_INT);
        $stmt->bindParam(':followed_id', $followedId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    public function countFollowers(int $userId): int {
        $sql = "SELECT COUNT(*) FROM follows WHERE followed_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function countFollowing(int $userId): int {
        $sql = "SELECT COUNT(*) FROM follows WHERE follower_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Users who follow $userId (newest follows first).
     */
    public function getFollowers(int $userId, int $limit = 50): array {
        $sql = "SELECT u.id, u.name, u.email, u.role, u.bio
                FROM follows f
                JOIN users u ON u.id = f.follower_id
                WHERE f.followed_id = :user_id
                ORDER BY f.created_at DESC, f.follower_id DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Users $userId follows (newest follows first).
     */
    public function getFollowing(int $userId, int $limit = 50): array {
        $sql = "SELECT u.id, u.name, u.email, u.role, u.bio
                FROM follows f
                JOIN users u ON u.id = f.followed_id
                WHERE f.follower_id = :user_id
                ORDER BY f.created_at DESC, f.followed_id DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Who-to-follow suggestions (WBS 3.3.5): users the viewer does not
     * already follow, excluding themselves, ordered by follower count.
     */
    public function getSuggestions(int $userId, int $limit = 5): array {
        $sql = "SELECT u.id, u.name, u.email, u.role, u.bio,
                       COUNT(f.follower_id) AS follower_count
                FROM users u
                LEFT JOIN follows f ON f.followed_id = u.id
                WHERE u.id <> :user_id
                  AND u.id NOT IN (SELECT followed_id FROM follows WHERE follower_id = :user_id)
                GROUP BY u.id, u.name, u.email, u.role, u.bio
                ORDER BY follower_count DESC, u.created_at DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
