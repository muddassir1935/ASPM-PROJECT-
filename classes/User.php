<?php
class User {
    private $db;

    /**
     * Designed profile cover styles (Sprint 3 cover picker).
     * 'signature' is the default brand duotone; the rest are CSS
     * variants rendered by the .profile-cover.cover-<style> classes.
     */
    public const COVER_STYLES = ['signature', 'cobalt', 'teal', 'sunset', 'violet', 'graphite'];
    public const DEFAULT_COVER_STYLE = 'signature';

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Whitelist a cover style value; anything unknown falls back
     * to the default so a bad stored/posted value can never break
     * the profile cover markup.
     */
    public static function normalizeCoverStyle($style): string {
        $clean = is_string($style) ? strtolower(trim($style)) : '';
        return in_array($clean, self::COVER_STYLES, true) ? $clean : self::DEFAULT_COVER_STYLE;
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
            "SELECT u.id, u.name, u.email, u.role, u.bio, u.cover_style, u.created_at,
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
     * $coverStyle is optional: null leaves the stored style untouched,
     * any other value is whitelisted (unknown values fall back to the
     * default 'signature' style).
     */
    public function updateProfile($userId, $name, $bio, $coverStyle = null): bool {
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

        $sql = "UPDATE users SET name = :name, bio = :bio";
        $cleanCover = null;
        if ($coverStyle !== null) {
            $cleanCover = self::normalizeCoverStyle((string)$coverStyle);
            $sql .= ", cover_style = :cover_style";
        }
        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $cleanName, PDO::PARAM_STR);
        $stmt->bindParam(':bio', $cleanBio, PDO::PARAM_STR);
        if ($cleanCover !== null) {
            $stmt->bindParam(':cover_style', $cleanCover, PDO::PARAM_STR);
        }
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Admin user list (Sprint 5 RBAC, admin panel): newest first,
     * searchable by name or email, paginated. Returns id, name, email,
     * role, created_at and post count per user.
     */
    public function listUsers(string $search = '', int $limit = 50, int $offset = 0): array {
        $clean = trim($search);
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $clean) . '%';
        $sql = "SELECT u.id, u.name, u.email, u.role, u.created_at,
                       (SELECT COUNT(*) FROM posts p WHERE p.user_id = u.id AND p.deleted_at IS NULL) AS post_count
                FROM users u";
        $params = [];
        if ($clean !== '') {
            $sql .= " WHERE (u.name LIKE :q ESCAPE '\\' OR u.email LIKE :q2 ESCAPE '\\')";
            $params[':q'] = $like;
            $params[':q2'] = $like;
        }
        $sql .= " ORDER BY u.created_at DESC, u.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Count users matching the admin list search (for pagination). */
    public function countUsers(string $search = ''): int {
        $clean = trim($search);
        $sql = "SELECT COUNT(*) FROM users u";
        $params = [];
        if ($clean !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $clean) . '%';
            $sql .= " WHERE (u.name LIKE :q ESCAPE '\\' OR u.email LIKE :q2 ESCAPE '\\')";
            $params[':q'] = $like;
            $params[':q2'] = $like;
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Assign a role to a user (Sprint 5 RBAC, admin panel only).
     * Only the three current role names are accepted — legacy values
     * ('admin', 'society') are rejected here, never stored. Returns
     * false for an invalid role, unknown user, or DB failure.
     */
    public function setRole($userId, $role): bool {
        if ((int)$userId <= 0) {
            return false;
        }
        require_once __DIR__ . '/Authorization.php';
        if (!class_exists('Authorization') || !Authorization::isValidRole($role)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :id");
        $stmt->bindValue(':role', strtolower(trim((string)$role)), PDO::PARAM_STR);
        $stmt->bindValue(':id', (int)$userId, PDO::PARAM_INT);
        try {
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /** How many system admins exist (guards against demoting the last one). */
    public function countSystemAdmins(): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = 'system_admin'");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Search users by NAME only (Sprint 3 explore page).
     * Sprint 5 (H5): matching on email let anyone harvest addresses
     * by searching "@nu.edu.pk" (or "a"), and the results displayed
     * them. Email is no longer matched or returned here; the admin
     * panel's listUsers() still searches email (admin-only).
     * The searching user is excluded from their own results.
     */
    public function searchUsers($query, $excludeUserId = 0, $limit = 20): array {
        $clean = trim((string)$query);
        if ($clean === '') {
            return [];
        }
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $clean) . '%';
        $stmt = $this->db->prepare(
            "SELECT u.id, u.name, u.role, u.bio,
                    (SELECT COUNT(*) FROM follows f WHERE f.followed_id = u.id) AS follower_count
             FROM users u
             WHERE u.name LIKE :q ESCAPE '\\'
               AND u.id <> :exclude_id
             ORDER BY u.name ASC
             LIMIT :limit"
        );
        $stmt->bindParam(':q', $like, PDO::PARAM_STR);
        $stmt->bindParam(':exclude_id', $excludeUserId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}