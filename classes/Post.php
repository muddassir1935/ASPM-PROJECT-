<?php
/**
 * Class Post
 * 
 * Domain Model for Microblogging Posts (Chirps/Announcements)
 * Adheres to OOP principles (Encapsulation, Single Responsibility, Dependency Injection)
 * Supports PDO with Prepared Statements for SQL injection immunity.
 */
class Post {
    private PDO $db;

    // Constraints
    public const MAX_LENGTH = 280;
    public const MIN_LENGTH = 1;

    /**
     * Post constructor.
     * @param PDO $dbConnection Injected PDO database connection
     */
    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Validate post text content.
     * 
     * @param string $content
     * @return array ['valid' => bool, 'error' => string|null, 'clean' => string]
     */
    public function validateContent(string $content): array {
        $clean = trim($content);
        $length = mb_strlen($clean, 'UTF-8');

        if ($length < self::MIN_LENGTH) {
            return [
                'valid' => false,
                'error' => 'Post content cannot be empty or contain only whitespace.',
                'clean' => ''
            ];
        }

        if ($length > self::MAX_LENGTH) {
            return [
                'valid' => false,
                'error' => 'Post cannot exceed ' . self::MAX_LENGTH . ' characters (currently ' . $length . ').',
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
                return (int)$this->db->lastInsertId();
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
     * @return array|false
     */
    public function getById(int $postId) {
        $sql = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                       u.name AS author_name, u.email AS author_email, u.role AS author_role
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = :id AND p.deleted_at IS NULL
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $postId, PDO::PARAM_INT);
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
     * @return array List of posts with author details
     */
    public function getFeed(int $limit = 50, int $offset = 0): array {
        $sql = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                       u.name AS author_name, u.email AS author_email, u.role AS author_role
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.deleted_at IS NULL
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
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
     * Get all posts created by a specific user.
     * 
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByUserId(int $userId, int $limit = 50, int $offset = 0): array {
        $sql = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                       u.name AS author_name, u.email AS author_email, u.role AS author_role
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.user_id = :user_id AND p.deleted_at IS NULL
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
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
     * Update an existing post. Only the original author can update their post.
     * 
     * @param int $postId
     * @param int $userId Current logged-in user ID
     * @param string $newContent
     * @return bool
     */
    public function update(int $postId, int $userId, string $newContent): bool {
        // Validation check
        $validation = $this->validateContent($newContent);
        if (!$validation['valid']) {
            return false;
        }

        // Authorization check: Verify post belongs to the user and is not soft-deleted
        $checkSql = "SELECT user_id FROM posts WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindParam(':id', $postId, PDO::PARAM_INT);
        $checkStmt->execute();
        $owner = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$owner || (int)$owner['user_id'] !== $userId) {
            return false; // Forbidden / Not found / Soft-deleted
        }

        $sql = "UPDATE posts
                SET content = :content, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':content', $validation['clean'], PDO::PARAM_STR);
        $stmt->bindParam(':id', $postId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Soft-delete a post (WBS 2.3.6). The row stays in the database with
     * deleted_at set, but it is hidden from getById, feeds and counts.
     * Only the author or an admin can delete a post.
     *
     * @param int $postId
     * @param int $userId Current logged-in user ID
     * @param string $userRole Role of the current user ('admin', 'student', etc.)
     * @return bool
     */
    public function delete(int $postId, int $userId, string $userRole = 'student'): bool {
        $checkSql = "SELECT user_id FROM posts WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindParam(':id', $postId, PDO::PARAM_INT);
        $checkStmt->execute();
        $owner = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$owner) {
            return false;
        }

        // Allow deletion if user is author OR user is admin
        $isAuthor = ((int)$owner['user_id'] === $userId);
        $isAdmin = ($userRole === 'admin');

        if (!$isAuthor && !$isAdmin) {
            return false; // Unauthorized
        }

        $sql = "UPDATE posts SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $postId, PDO::PARAM_INT);

        return $stmt->execute();
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
