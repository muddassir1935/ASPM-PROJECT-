<?php
/**
 * Class Hashtag
 *
 * Domain model for Hashtags & Discovery (Sprint 4, WBS 4.2).
 * Extracts, normalizes, indexes hashtags from post content,
 * renders clickable links, and provides trending hashtag aggregation.
 */
class Hashtag {
    private PDO $db;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Extract hashtag names from text (without '#').
     * Returns deduplicated, lowercased tags (1 to 50 chars).
     */
    public static function extractTags(string $content): array {
        if (trim($content) === '') {
            return [];
        }

        preg_match_all('/#([a-zA-Z0-9_]{1,50})\b/u', $content, $matches);
        if (empty($matches[1])) {
            return [];
        }

        $tags = array_map(function($t) {
            return mb_strtolower(trim($t), 'UTF-8');
        }, $matches[1]);

        return array_values(array_unique(array_filter($tags, fn($t) => $t !== '')));
    }

    /**
     * Synchronize a post's hashtags with the database.
     * Inserts new hashtags into `hashtags` and updates `post_hashtags`.
     */
    public function syncPostTags(int $postId, array $tags): void {
        if ($postId <= 0) {
            return;
        }

        // Remove existing associations for this post
        $deleteSql = "DELETE FROM post_hashtags WHERE post_id = :post_id";
        $deleteStmt = $this->db->prepare($deleteSql);
        $deleteStmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
        $deleteStmt->execute();

        if (empty($tags)) {
            return;
        }

        $selectSql = "SELECT id FROM hashtags WHERE tag = :tag LIMIT 1";
        $selectStmt = $this->db->prepare($selectSql);

        $insertTagSql = "INSERT INTO hashtags (tag, created_at) VALUES (:tag, CURRENT_TIMESTAMP)";
        $insertTagStmt = $this->db->prepare($insertTagSql);

        $linkSql = "INSERT INTO post_hashtags (post_id, hashtag_id, created_at)
                    VALUES (:post_id, :hashtag_id, CURRENT_TIMESTAMP)";
        $linkStmt = $this->db->prepare($linkSql);

        foreach ($tags as $tag) {
            $tag = mb_strtolower(trim($tag), 'UTF-8');
            if ($tag === '') continue;

            $selectStmt->bindParam(':tag', $tag, PDO::PARAM_STR);
            $selectStmt->execute();
            $tagId = $selectStmt->fetchColumn();

            if (!$tagId) {
                try {
                    $insertTagStmt->bindParam(':tag', $tag, PDO::PARAM_STR);
                    $insertTagStmt->execute();
                    $tagId = (int)$this->db->lastInsertId();
                } catch (PDOException $e) {
                    // Race condition or duplicate: re-fetch
                    $selectStmt->execute();
                    $tagId = $selectStmt->fetchColumn();
                }
            }

            if ($tagId) {
                try {
                    $linkStmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
                    $linkStmt->bindValue(':hashtag_id', (int)$tagId, PDO::PARAM_INT);
                    $linkStmt->execute();
                } catch (PDOException $e) {
                    // Association might already exist
                }
            }
        }
    }

    /**
     * Convert #hashtags in safe HTML text to clickable links.
     * Expects input that has already been sanitized with htmlspecialchars.
     */
    public static function renderHashtagLinks(string $escapedHtml): string {
        return preg_replace_callback(
            // (?<!&) is load-bearing: htmlspecialchars turns an apostrophe into
            // the entity &#039; — without the lookbehind, "#039" would be
            // linkified and every apostrophe in a post would render broken.
            '/(?<!&)#([a-zA-Z0-9_]{1,50})\b/u',
            function ($matches) {
                $rawTag = $matches[1];
                $lowerTag = mb_strtolower($rawTag, 'UTF-8');
                return '<a href="explore.php?tag=' . urlencode($lowerTag) . '" class="hashtag-link">#' . $rawTag . '</a>';
            },
            $escapedHtml
        );
    }

    /**
     * Get top trending hashtags ordered by post usage (non-deleted posts only).
     */
    public function getTrending(int $limit = 5): array {
        $sql = "SELECT h.id, h.tag, COUNT(ph.post_id) AS post_count
                FROM hashtags h
                JOIN post_hashtags ph ON h.id = ph.hashtag_id
                JOIN posts p ON ph.post_id = p.id
                WHERE p.deleted_at IS NULL
                GROUP BY h.id, h.tag
                HAVING post_count > 0
                ORDER BY post_count DESC, h.id DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get posts tagged with a specific hashtag.
     */
    public function getPostsByTag(string $tag, int $viewerId = 0, int $limit = 50, int $offset = 0): array {
        $cleanTag = mb_strtolower(trim($tag), 'UTF-8');
        if ($cleanTag === '') {
            return [];
        }

        $viewerSubquery = $viewerId > 0
            ? ", (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = :viewer_id) AS is_liked"
            : ", 0 AS is_liked";

        $sql = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                       u.name AS author_name, u.email AS author_email, u.role AS author_role,
                       (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id AND r.deleted_at IS NULL) AS reply_count,
                       (SELECT COUNT(*) FROM likes l2 WHERE l2.post_id = p.id) AS like_count
                       {$viewerSubquery}
                FROM posts p
                JOIN post_hashtags ph ON p.id = ph.post_id
                JOIN hashtags h ON ph.hashtag_id = h.id
                JOIN users u ON p.user_id = u.id
                WHERE h.tag = :tag AND p.deleted_at IS NULL
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tag', $cleanTag, PDO::PARAM_STR);
        if ($viewerId > 0) {
            $stmt->bindValue(':viewer_id', $viewerId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

