<?php
/**
 * Class Post
 * 
 * Domain model responsible for managing post data, CRUD operations,
 * data validation, soft deletes, and feed generation.
 * Follows OOP design principles and uses PDO prepared statements for security.
 */
require_once __DIR__ . '/Hashtag.php';
require_once __DIR__ . '/Cache.php';

class Post {
    private PDO $db;

    const MAX_LENGTH = 280;
    const MIN_LENGTH = 1;

    /**
     * Post constructor.
     * 
     * @param PDO $dbConnection Injected PDO database connection
     */
    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Validate post content against character bounds and clean whitespace.
     * 
     * @param string $content
     * @return array ['valid' => bool, 'error' => ?string, 'clean' => string]
     */
    public function validateContent(string $content): array {
        $clean = trim($content);
        $length = mb_strlen($clean, 'UTF-8');

        if ($length < self::MIN_LENGTH) {
            return [
                'valid' => false,
                'error' => 'Post content cannot be empty.',
                'clean' => $clean
            ];
        }

        if ($length > self::MAX_LENGTH) {
            return [
                'valid' => false,
                'error' => "Post exceeds the maximum character limit of " . self::MAX_LENGTH . " characters.",
                'clean' => $clean
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'clean' => $clean
        ];
    }

    /**
     * Create a new post.
     *
     * @param int $userId Author ID
     * @param string $content Raw post text
     * @param bool $isAnnouncement Society announcement flag. The caller
     *        (PostController) is responsible for the permission check —
     *        only society_admin / system_admin may set this.
     * @return int|false The newly created post ID, or false on validation/db failure
     */
    public function create(int $userId, string $content, bool $isAnnouncement = false) {
        if ($userId <= 0) {
            return false;
        }

        $validation = $this->validateContent($content);
        if (!$validation['valid']) {
            return false;
        }

        $cleanContent = $validation['clean'];

        $sql = "INSERT INTO posts (user_id, content, is_announcement, created_at, updated_at)
                VALUES (:user_id, :content, :is_announcement, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':content', $cleanContent, PDO::PARAM_STR);
        $announcementFlag = $isAnnouncement ? 1 : 0;
        $stmt->bindParam(':is_announcement', $announcementFlag, PDO::PARAM_INT);

        // Sprint 5 final fix (security M11): post + tag sync must be atomic.
        // A tag failure used to leave the post saved while reporting failure.
        // Cache invalidation stays outside the transaction (not rollback-able).
        try {
            $this->db->beginTransaction();
            if (!$stmt->execute()) {
                $this->db->rollBack();
                return false;
            }
            $newPostId = (int)$this->db->lastInsertId();

            // Sprint 4: Extract and link hashtags
            $tags = Hashtag::extractTags($cleanContent);
            $hashtagModel = new Hashtag($this->db);
            $hashtagModel->syncPostTags($newPostId, $tags);

            $this->db->commit();

            // Sprint 4: Invalidate feed cache
            Cache::getInstance()->deleteByPrefix('feed_');

            return $newPostId;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Post creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve a single post by ID along with author information.
     * 
     * @param int $postId
     * @param int $viewerId Optional viewer user ID to check like/bookmark status
     * @return array|false
     */
    public function getById(int $postId, int $viewerId = 0) {
        $viewerSubquery = $viewerId > 0
            ? ", (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = :viewer_id) AS is_liked,
               (SELECT COUNT(*) FROM bookmarks b WHERE b.post_id = p.id AND b.user_id = :viewer_id2) AS is_bookmarked"
            : ", 0 AS is_liked, 0 AS is_bookmarked";

        $sql = "SELECT p.id, p.user_id, p.content, p.is_announcement, p.created_at, p.updated_at,
                       u.name AS author_name, u.role AS author_role,
                       (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id AND r.deleted_at IS NULL) AS reply_count,
                       (SELECT COUNT(*) FROM likes l2 WHERE l2.post_id = p.id) AS like_count
                       {$viewerSubquery}
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = :id AND p.deleted_at IS NULL
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $postId, PDO::PARAM_INT);
        if ($viewerId > 0) {
            $stmt->bindValue(':viewer_id', $viewerId, PDO::PARAM_INT);
            $stmt->bindValue(':viewer_id2', $viewerId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($post) {
            $post['time_ago'] = $this->formatTimeAgo($post['created_at']);
        }
        return $post;
    }

    /**
     * Get the dynamic home feed timeline (Newest posts first).
     *
     * Caching (audit C9): only the slow-changing part of each row (post
     * text, author, timestamps) is cached — and that part is identical for
     * every viewer, so ONE cache entry per page now serves all users.
     * Like/reply counts and the viewer's is_liked / is_bookmarked are
     * attached live by attachLiveState() with a single query for the page,
     * so likes, bookmarks and replies no longer have to wipe every cached
     * feed, and they can never show stale numbers. time_ago is computed
     * after the cache read, so "just now" does not freeze for 5 minutes.
     *
     * @param int $limit Max posts to return
     * @param int $offset Offset for pagination
     * @param int $viewerId Optional viewer user ID
     * @param bool $bypassCache Skip the cache (admin moderation view)
     * @return array List of posts with author details
     */
    public function getFeed(int $limit = 50, int $offset = 0, int $viewerId = 0, bool $bypassCache = false): array {
        $runQuery = function() use ($limit, $offset) {
            $sql = "SELECT p.id, p.user_id, p.content, p.is_announcement, p.created_at, p.updated_at,
                           u.name AS author_name, u.role AS author_role, u.email AS author_email
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.deleted_at IS NULL
                    ORDER BY p.created_at DESC, p.id DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        };

        $rows = $bypassCache
            ? $runQuery()
            : Cache::getInstance()->remember("feed_global_{$limit}_{$offset}", 300, $runQuery);
        return $this->attachLiveState($rows, $viewerId);
    }

    /**
     * Attach the fast-changing, viewer-specific fields to a page of post
     * rows with ONE query (audit C9): reply_count, like_count, and — for a
     * signed-in viewer — is_liked / is_bookmarked. Also stamps time_ago.
     * Row order is preserved.
     */
    private function attachLiveState(array $rows, int $viewerId): array {
        if (empty($rows)) {
            return [];
        }
        $ids = array_map(static fn($r) => (int)$r['id'], $rows);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = [];
        $viewerCols = ", 0 AS is_liked, 0 AS is_bookmarked";
        if ($viewerId > 0) {
            $viewerCols = ", (SELECT COUNT(*) FROM likes lv WHERE lv.post_id = p.id AND lv.user_id = ?) AS is_liked,
                            (SELECT COUNT(*) FROM bookmarks b WHERE b.post_id = p.id AND b.user_id = ?) AS is_bookmarked";
            $params[] = $viewerId;
            $params[] = $viewerId;
        }
        $sql = "SELECT p.id,
                       (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id AND r.deleted_at IS NULL) AS reply_count,
                       (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count
                       {$viewerCols}
                FROM posts p
                WHERE p.id IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $i = 1;
        foreach (array_merge($params, $ids) as $value) {
            $stmt->bindValue($i++, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        $live = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $live[(int)$row['id']] = $row;
        }

        foreach ($rows as &$row) {
            $state = $live[(int)$row['id']] ?? [];
            $row['reply_count'] = (int)($state['reply_count'] ?? 0);
            $row['like_count'] = (int)($state['like_count'] ?? 0);
            $row['is_liked'] = (int)($state['is_liked'] ?? 0);
            $row['is_bookmarked'] = (int)($state['is_bookmarked'] ?? 0);
            $row['time_ago'] = self::formatTimeAgo($row['created_at']);
        }
        unset($row);
        return $rows;
    }

    /**
     * Get all posts created by a specific user.
     * 
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param int $viewerId
     * @return array
     */
    public function getByUserId(int $userId, int $limit = 50, int $offset = 0, int $viewerId = 0): array {
        $viewerSubquery = $viewerId > 0
            ? ", (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = :viewer_id) AS is_liked,
               (SELECT COUNT(*) FROM bookmarks b WHERE b.post_id = p.id AND b.user_id = :viewer_id2) AS is_bookmarked"
            : ", 0 AS is_liked, 0 AS is_bookmarked";

        $sql = "SELECT p.id, p.user_id, p.content, p.is_announcement, p.created_at, p.updated_at,
                       u.name AS author_name, u.role AS author_role,
                       (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id AND r.deleted_at IS NULL) AS reply_count,
                       (SELECT COUNT(*) FROM likes l2 WHERE l2.post_id = p.id) AS like_count
                       {$viewerSubquery}
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.user_id = :user_id AND p.deleted_at IS NULL
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if ($viewerId > 0) {
            $stmt->bindValue(':viewer_id', $viewerId, PDO::PARAM_INT);
            $stmt->bindValue(':viewer_id2', $viewerId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($posts as &$post) {
            $post['time_ago'] = $this->formatTimeAgo($post['created_at']);
        }
        return $posts;
    }

    /**
     * Update an existing post. Must verify ownership or authorization first.
     * 
     * @param int $postId
     * @param int $userId Requesting user ID
     * @param string $newContent
     * @return bool
     */
    public function update(int $postId, int $userId, string $newContent): bool {
        if ($postId <= 0 || $userId <= 0) {
            return false;
        }

        $existing = $this->getById($postId);
        if (!$existing) {
            return false;
        }

        if ((int)$existing['user_id'] !== $userId) {
            return false; // Unauthorized
        }

        $validation = $this->validateContent($newContent);
        if (!$validation['valid']) {
            return false;
        }

        $cleanContent = $validation['clean'];

        $sql = "UPDATE posts 
                SET content = :content, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':content', $cleanContent, PDO::PARAM_STR);
        $stmt->bindParam(':id', $postId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        // Sprint 5 final fix (security M11): same atomicity as create() —
        // content update + tag re-sync commit together or not at all.
        try {
            $this->db->beginTransaction();
            $res = $stmt->execute();
            if ($res) {
                // Sprint 4: Re-extract and sync hashtags
                $tags = Hashtag::extractTags($cleanContent);
                $hashtagModel = new Hashtag($this->db);
                $hashtagModel->syncPostTags($postId, $tags);
            }
            if ($res) {
                $this->db->commit();
                Cache::getInstance()->deleteByPrefix('feed_');
            } else {
                $this->db->rollBack();
            }
            return $res;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Post update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Soft delete a post by setting deleted_at to CURRENT_TIMESTAMP.
     * 
     * @param int $postId
     * @param int $userId Requesting user
     * @param bool $canDeleteAny Whether the caller holds the
     *        'post.delete_any' permission (RBAC, Sprint 5 — the caller
     *        decides via Authorization, this method only enforces)
     * @param bool $canDeleteOwn Whether the caller holds 'post.delete_own'
     *        (audit C7; defaults to true for backward compatibility)
     * @return bool
     */
    public function delete(int $postId, int $userId, bool $canDeleteAny = false, bool $canDeleteOwn = true): bool {
        if ($postId <= 0 || $userId <= 0) {
            return false;
        }

        $existing = $this->getById($postId);
        if (!$existing) {
            return false;
        }

        $isAuthor = ((int)$existing['user_id'] === $userId);

        if (!($isAuthor && $canDeleteOwn) && !$canDeleteAny) {
            return false; // Unauthorized
        }

        $sql = "UPDATE posts SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $postId, PDO::PARAM_INT);

        $res = $stmt->execute();
        if ($res) {
            Cache::getInstance()->deleteByPrefix('feed_');
        }
        return $res;
    }

    /**
     * Personalized home feed (Sprint 3, WBS 3.2): the viewer's own posts
     * plus posts from users they follow, newest first.
     * Audit C9: the cached part is the post/author rows only (key
     * feed_personalized_{user}_...); counts and like/bookmark state are
     * attached live, so only post changes and the user's own follows
     * need to invalidate it.
     */
    public function getPersonalizedFeed(int $userId, int $limit = 50, int $offset = 0): array {
        $cacheKey = self::personalizedCachePrefix($userId) . "{$limit}_{$offset}";

        $rows = Cache::getInstance()->remember($cacheKey, 300, function() use ($userId, $limit, $offset) {
            $sql = "SELECT p.id, p.user_id, p.content, p.is_announcement, p.created_at, p.updated_at,
                           u.name AS author_name, u.role AS author_role, u.email AS author_email
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.deleted_at IS NULL
                      AND (p.user_id = :user_id
                           OR p.user_id IN (SELECT followed_id FROM follows WHERE follower_id = :user_id2))
                    ORDER BY p.created_at DESC, p.id DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id2', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
        return $this->attachLiveState($rows, $userId);
    }

    /**
     * Cache-key prefix of one user's personalized feed pages. The trailing
     * underscore keeps user 1's prefix from matching user 12's keys.
     */
    public static function personalizedCachePrefix(int $userId): string {
        return "feed_personalized_{$userId}_";
    }

    public function countPersonalized(int $userId): int {
        $sql = "SELECT COUNT(*) FROM posts p
                WHERE p.deleted_at IS NULL
                  AND (p.user_id = :user_id
                       OR p.user_id IN (SELECT followed_id FROM follows WHERE follower_id = :user_id))";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Search post content (Sprint 3 explore page, WBS 3.3.4).
     * LIKE wildcards in the query are escaped so they match literally.
     */
    public function searchPosts(string $query, int $limit = 50, int $offset = 0, int $viewerId = 0): array {
        $clean = trim($query);
        if ($clean === '') {
            return [];
        }
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $clean) . '%';

        $viewerSubquery = $viewerId > 0
            ? ", (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = :viewer_id) AS is_liked,
               (SELECT COUNT(*) FROM bookmarks b WHERE b.post_id = p.id AND b.user_id = :viewer_id2) AS is_bookmarked"
            : ", 0 AS is_liked, 0 AS is_bookmarked";

        $sql = "SELECT p.id, p.user_id, p.content, p.is_announcement, p.created_at, p.updated_at,
                       u.name AS author_name, u.role AS author_role,
                       (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id AND r.deleted_at IS NULL) AS reply_count,
                       (SELECT COUNT(*) FROM likes l2 WHERE l2.post_id = p.id) AS like_count
                       {$viewerSubquery}
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.deleted_at IS NULL AND p.content LIKE :q ESCAPE '!'
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':q', $like, PDO::PARAM_STR);
        if ($viewerId > 0) {
            $stmt->bindValue(':viewer_id', $viewerId, PDO::PARAM_INT);
            $stmt->bindValue(':viewer_id2', $viewerId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($posts as &$post) {
            $post['time_ago'] = $this->formatTimeAgo($post['created_at']);
        }
        return $posts;
    }

    public function countSearch(string $query): int {
        $clean = trim($query);
        if ($clean === '') {
            return 0;
        }
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $clean) . '%';
        $sql = "SELECT COUNT(*) FROM posts p
                WHERE p.deleted_at IS NULL AND p.content LIKE :q ESCAPE '!'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':q', $like, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Count total posts by a specific user.
     * 
     * @param int $userId
     * @return int
     */
    public function countByUser(int $userId): int {
        $sql = "SELECT COUNT(*) FROM posts WHERE user_id = :user_id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Count all posts in the system.
     * 
     * @return int
     */
    public function countAll(): int {
        $sql = "SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL";
        $stmt = $this->db->query($sql);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Helper to compute human-readable relative time string.
     * Static (pure function) so other models (Like, Hashtag, Reply)
     * can stamp the same relative time on their rows — every list the
     * UI renders must carry time_ago, never a raw DB timestamp.
     *
     * @param string $datetime
     * @return string
     */
    public static function formatTimeAgo(string $datetime): string {
        $timestamp = self::parseDbTimestamp($datetime);
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

    /**
     * Parse a timestamp read from the database (audit C2).
     *
     * Every DB timestamp is UTC: SQLite's CURRENT_TIMESTAMP is always
     * UTC, and Database pins the MySQL session time zone to +00:00. The
     * old code parsed them with strtotime() in PHP's zone (Asia/Karachi),
     * so on SQLite — or any MySQL server not running in PKT — a brand-new
     * post showed "5h ago". Parse as UTC here; date() then renders the
     * result in the app's display zone.
     *
     * @return int|false Unix timestamp, or false when unparseable
     */
    public static function parseDbTimestamp(?string $datetime) {
        if ($datetime === null || trim($datetime) === '') {
            return false;
        }
        try {
            $dt = new DateTimeImmutable(trim($datetime), new DateTimeZone('UTC'));
            return $dt->getTimestamp();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Format a DB timestamp for display in the app's time zone, e.g.
     * Post::formatDbDate($row['created_at'], 'M j, Y'). Returns '' when
     * the value cannot be parsed.
     */
    public static function formatDbDate(?string $datetime, string $format): string {
        $timestamp = self::parseDbTimestamp($datetime);
        return $timestamp === false ? '' : date($format, $timestamp);
    }
}
