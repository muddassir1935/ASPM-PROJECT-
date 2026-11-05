<?php
class User {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Register a new user with password hashing.
     * Ideal for Unit Testing.
     */
    public function register($name, $email, $password) {
        if(empty($name) || empty($email) || empty($password)) {
            return false;
        }

        // Strong password validation: min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
        if (strlen($password) < 8 || 
            !preg_match('/[A-Z]/', $password) || 
            !preg_match('/[a-z]/', $password) || 
            !preg_match('/[0-9]/', $password) || 
            !preg_match('/[\W_]/', $password)) {
            return false;
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':hash', $hash);
        
        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false; // E.g. Duplicate email
        }
    }

    /**
     * Verify user password during login.
     * Ideal for Unit Testing.
     */
    public function verifyPassword($email, $password) {
        $stmt = $this->db->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }

    /**
     * Public profile data for one user, including follower counts.
     * Returns false when the user does not exist.
     */
    public function getById($userId) {
        if ((int)$userId <= 0) {
            return false;
        }
        $stmt = $this->db->prepare(
            "SELECT u.id, u.name, u.email, u.role, u.bio, u.created_at,
                    (SELECT COUNT(*) FROM follows f WHERE f.followed_id = u.id) AS follower_count,
                    (SELECT COUNT(*) FROM follows f WHERE f.follower_id = u.id) AS following_count
             FROM users u WHERE u.id = :id LIMIT 1"
        );
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? $user : false;
    }

    /**
     * Update a user's display name and bio (Sprint 3 profile edit).
     * Name must be non-empty (max 255 chars); bio is optional, max 500 chars.
     */
    public function updateProfile($userId, $name, $bio): bool {
        if ((int)$userId <= 0) {
            return false;
        }
        $cleanName = trim((string)$name);
        if ($cleanName === '' || mb_strlen($cleanName, 'UTF-8') > 255) {
            return false;
        }
        $cleanBio = trim((string)($bio ?? ''));
        if (mb_strlen($cleanBio, 'UTF-8') > 500) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE users SET name = :name, bio = :bio WHERE id = :id");
        $stmt->bindParam(':name', $cleanName, PDO::PARAM_STR);
        $stmt->bindParam(':bio', $cleanBio, PDO::PARAM_STR);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Search users by name or email (Sprint 3 explore page).
     * The searching user is excluded from their own results.
     */
    public function searchUsers($query, $excludeUserId = 0, $limit = 20): array {
        $clean = trim((string)$query);
        if ($clean === '') {
            return [];
        }
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $clean) . '%';
        $stmt = $this->db->prepare(
            "SELECT u.id, u.name, u.email, u.role, u.bio,
                    (SELECT COUNT(*) FROM follows f WHERE f.followed_id = u.id) AS follower_count
             FROM users u
             WHERE (u.name LIKE :q ESCAPE '\\' OR u.email LIKE :q2 ESCAPE '\\')
               AND u.id <> :exclude_id
             ORDER BY u.name ASC
             LIMIT :limit"
        );
        $stmt->bindParam(':q', $like, PDO::PARAM_STR);
        $stmt->bindParam(':q2', $like, PDO::PARAM_STR);
        $stmt->bindParam(':exclude_id', $excludeUserId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}