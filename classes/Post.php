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
     * @return int|false The newly created post ID, or false on validation/db failure
     */
    public function create(int $userId, string $content) {
        if ($userId <= 0) {
            return false;
        }

        $validation = $this->validateContent($content);
        if (!$validation['valid']) {
            return false;
        }

        $cleanContent = $validation['clean'];

        $sql = "INSERT INTO posts (user_id, content, created_at, updated_at) 
                VALUES (:user_id, :content, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':content', $cleanContent, PDO::PARAM_STR);

        try {
            if ($stmt->execute()) {
                $newPostId = (int)$this->db->lastInsertId();

                // Sprint 4: Extract and link hashtags
                $tags = Hashtag::extractTags($cleanContent);
                $hashtagModel = new Hashtag($this->db);
                $hashtagModel->syncPostTags($newPostId, $tags);

                // Sprint 4: Invalidate feed cache
                Cache::getInstance()->deleteByPrefix('feed_');

                return $newPostId;
            }
            return false;
        } catch (PDOException $e) {
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

        $sql = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                       u.name AS author_name, u.email AS author_email, u.role AS author_role,
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
     * @param int $limit Max posts to return
     * @param int $offset Offset for pagination
     * @param int $viewerId Optional viewer user ID
     * @return array List of posts with author details
     */
    public function getFeed(int $limit = 50, int $offset = 0, int $viewerId = 0, bool $bypassCache = false): array {
        // Sprint 4 (WBS 4.3.4): the viewer-agnostic global timeline is cached
        // for 5 minutes under the feed_ prefix; create/update/delete/like
        // invalidate it. Viewer-specific rows (is_liked / is_bookmarked) are
        // never shared across users, so calls with a viewer bypass the cache.
        $runQuery = function() use ($limit, $offset, $viewerId) {
            $viewerSubquery = $viewerId > 0
                ? ", (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = :viewer_id) AS is_liked,
                   (SELECT COUNT(*) FROM bookmarks b WHERE b.post_id = p.id AND b.user_id = :viewer_id2) AS is_bookmarked"
                : ", 0 AS is_liked, 0 AS is_bookmarked";

            $sql = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                           u.name AS author_name, u.email AS author_email, u.role AS author_role,
                           (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id AND r.deleted_at IS NULL) AS reply_count,
                           (SELECT COUNT(*) FROM likes l2 WHERE l2.post_id = p.id) AS like_count
                           {$viewerSubquery}
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.deleted_at IS NULL
                    ORDER BY p.created_at DESC, p.id DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
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
        };

        if ($viewerId === 0 && !$bypassCache) {
            return Cache::getInstance()->remember("feed_global_{$limit}_{$offset}", 300, $runQuery);
        }
        return $runQuery();
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

        $sql = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                       u.name AS author_name, u.email AS author_email, u.role AS author_role,
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

        try {
            $res = $stmt->execute();
            if ($res) {
                // Sprint 4: Re-extract and sync hashtags
                $tags = Hashtag::extractTags($cleanContent);
                $hashtagModel = new Hashtag($this->db);
                $hashtagModel->syncPostTags($postId, $tags);

                Cache::getInstance()->deleteByPrefix('feed_');
            }
            return $res;
        } catch (PDOException $e) {
            error_log("Post update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Soft delete a post by setting deleted_at to CURRENT_TIMESTAMP.
     * 
     * @param int $postId
     * @param int $userId Requesting user
     * @param bool|string $isAdmin Whether the user has admin privileges (bool or role string)
     * @return bool
     */
    public function delete(int $postId, int $userId, $isAdmin = false): bool {
        if ($postId <= 0 || $userId <= 0) {
            return false;
        }

        $existing = $this->getById($postId);
        if (!$existing) {
            return false;
        }

        $isAuthor = ((int)$existing['user_id'] === $userId);
        $hasAdminPrivilege = is_bool($isAdmin) ? $isAdmin : ($isAdmin === 'admin');

        if (!$isAuthor && !$hasAdminPrivilege) {
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
     * Sprint 4: Joined with like counts and viewer like/bookmark status.
     */
    public function getPersonalizedFeed(int $userId, int $limit = 50, int $offset = 0): array {
        $cacheKey = "feed_personalized_{$userId}_{$limit}_{$offset}";
        $cache = Cache::getInstance();

        // WBS 4.3.4: timeline cache TTL is 5 minutes (300s), not 60s.
        return $cache->remember($cacheKey, 300, function() use ($userId, $limit, $offset) {
            $sql = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                           u.name AS author_name, u.email AS author_email, u.role AS author_role,
                           (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id AND r.deleted_at IS NULL) AS reply_count,
                           (SELECT COUNT(*) FROM likes l2 WHERE l2.post_id = p.id) AS like_count,
                           (SELECT COUNT(*) FROM likes l3 WHERE l3.post_id = p.id AND l3.user_id = :viewer_id) AS is_liked,
                           (SELECT COUNT(*) FROM bookmarks b WHERE b.post_id = p.id AND b.user_id = :viewer_id2) AS is_bookmarked
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.deleted_at IS NULL
                      AND (p.user_id = :user_id
                           OR p.user_id IN (SELECT followed_id FROM follows WHERE follower_id = :user_id2))
                    ORDER BY p.created_at DESC, p.id DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':viewer_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':viewer_id2', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id2', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($posts as &$post) {
                $post['time_ago'] = $this->formatTimeAgo($post['created_at']);
            }
            return $posts;
        });
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
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $clean) . '%';

        $viewerSubquery = $viewerId > 0
            ? ", (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = :viewer_id) AS is_liked,
               (SELECT COUNT(*) FROM bookmarks b WHERE b.post_id = p.id AND b.user_id = :viewer_id2) AS is_bookmarked"
            : ", 0 AS is_liked, 0 AS is_bookmarked";

        $sql = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                       u.name AS author_name, u.email AS author_email, u.role AS author_role,
                       (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id AND r.deleted_at IS NULL) AS reply_count,
                       (SELECT COUNT(*) FROM likes l2 WHERE l2.post_id = p.id) AS like_count
                       {$viewerSubquery}
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.deleted_at IS NULL AND p.content LIKE :q ESCAPE '\\'
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
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $clean) . '%';
        $sql = "SELECT COUNT(*) FROM posts p
                WHERE p.deleted_at IS NULL AND p.content LIKE :q ESCAPE '\\'";
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
     * 
     * @param string $datetime
     * @return string
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
