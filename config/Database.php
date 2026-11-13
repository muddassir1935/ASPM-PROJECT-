<?php
class Database {
    private static $instance = null;
    private $connection;
    private $driver = 'mysql';

    private $host = 'localhost';
    private $db   = 'campusconnect';
    private $user = 'root';
    private $pass = '';

    private function __construct() {
        // Test mode (Sprint 5, H4): when CAMPUSCONNECT_TEST_DB is set,
        // skip MySQL entirely — the catch block below opens the isolated
        // throwaway SQLite DB instead of the live one, so the suites can
        // never pollute the demo database again. Set by
        // tests/bootstrap.php before the first getInstance() call.
        $testDb = getenv('CAMPUSCONNECT_TEST_DB');
        $testMode = is_string($testDb) && $testDb !== '';
        try {
            if ($testMode) {
                throw new PDOException('Test mode: use isolated SQLite DB.');
            }
            // Attempt MySQL connection with a short timeout
            $this->connection = new PDO("mysql:host={$this->host};dbname={$this->db};charset=utf8mb4", $this->user, $this->pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2
            ]);
            $this->driver = 'mysql';
        } catch(PDOException $e) {
            // Fallback to SQLite (Zero-Config mode so it works even if XAMPP is off)
            // — but LOUDLY, not silently (Sprint 5, H3). The fallback is a
            // DIFFERENT database: logins fail and written data vanishes once
            // MySQL returns, so a silent switch is a data-split bug. The
            // banner in includes/header.php tells the user; this log line
            // tells the operator. (Test mode skips the log: it is deliberate.)
            $sqlitePath = $testMode ? $testDb : __DIR__ . '/../db/campusconnect.sqlite';
            if (!$testMode) {
                error_log("CampusConnect: MySQL unreachable ({$e->getMessage()}); using SQLite fallback at {$sqlitePath}.");
            }
            $this->connection = new PDO("sqlite:" . $sqlitePath);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->driver = 'sqlite';

            // Auto-initialize schema if needed
            $this->connection->exec("PRAGMA foreign_keys = ON;");
            $this->connection->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    password_hash TEXT NOT NULL,
                    role TEXT DEFAULT 'student',
                    bio TEXT DEFAULT NULL,
                    cover_style TEXT NOT NULL DEFAULT 'signature',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS posts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    content TEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    deleted_at DATETIME DEFAULT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS follows (
                    follower_id INTEGER NOT NULL,
                    followed_id INTEGER NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (follower_id, followed_id),
                    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE,
                    CHECK (follower_id <> followed_id)
                );

                CREATE TABLE IF NOT EXISTS replies (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    post_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    content TEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    deleted_at DATETIME DEFAULT NULL,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS bookmarks (
                    user_id INTEGER NOT NULL,
                    post_id INTEGER NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (user_id, post_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS sessions (
                    id TEXT NOT NULL PRIMARY KEY,
                    user_id INTEGER DEFAULT NULL,
                    data TEXT NOT NULL,
                    last_activity INTEGER NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS likes (
                    user_id INTEGER NOT NULL,
                    post_id INTEGER NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (user_id, post_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS hashtags (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tag TEXT UNIQUE NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS post_hashtags (
                    post_id INTEGER NOT NULL,
                    hashtag_id INTEGER NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (post_id, hashtag_id),
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                    FOREIGN KEY (hashtag_id) REFERENCES hashtags(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS notifications (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    actor_id INTEGER DEFAULT NULL,
                    type TEXT NOT NULL,
                    reference_id INTEGER DEFAULT NULL,
                    message TEXT NOT NULL,
                    is_read INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE
                );

                -- Sprint 5 RBAC: login brute-force protection. One row per
                -- (email, IP) pair; see classes/LoginRateLimiter.php.
                CREATE TABLE IF NOT EXISTS login_attempts (
                    email TEXT NOT NULL,
                    ip TEXT NOT NULL,
                    attempts INTEGER NOT NULL DEFAULT 0,
                    last_attempt INTEGER NOT NULL DEFAULT 0,
                    locked_until INTEGER NOT NULL DEFAULT 0,
                    PRIMARY KEY (email, ip)
                );

                CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id);
                CREATE INDEX IF NOT EXISTS idx_posts_created_at ON posts(created_at DESC);
                CREATE INDEX IF NOT EXISTS idx_follows_followed_id ON follows(followed_id);
                CREATE INDEX IF NOT EXISTS idx_replies_post_id ON replies(post_id);
                CREATE INDEX IF NOT EXISTS idx_replies_created_at ON replies(created_at);
                CREATE INDEX IF NOT EXISTS idx_bookmarks_post_id ON bookmarks(post_id);
                CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions(user_id);
                CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions(last_activity);
                CREATE INDEX IF NOT EXISTS idx_likes_post_id ON likes(post_id);
                CREATE INDEX IF NOT EXISTS idx_hashtags_tag ON hashtags(tag);
                CREATE INDEX IF NOT EXISTS idx_post_hashtags_tag ON post_hashtags(hashtag_id);
                CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read);
                CREATE INDEX IF NOT EXISTS idx_notifications_user_created ON notifications(user_id, created_at DESC);
            ");

            // Migrations for SQLite files created before Sprint 3:
            // add the users.bio column when an older users table lacks it.
            $userColumns = $this->connection->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
            $userColumnNames = array_column($userColumns, 'name');
            if (!in_array('bio', $userColumnNames, true)) {
                $this->connection->exec("ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL");
            }

            // Migration for SQLite files created before the cover-style
            // picker: add the users.cover_style column when missing.
            if (!in_array('cover_style', $userColumnNames, true)) {
                $this->connection->exec("ALTER TABLE users ADD COLUMN cover_style TEXT NOT NULL DEFAULT 'signature'");
            }

            // Migration for SQLite files created before soft-delete (WBS 2.3.6):
            // add the deleted_at column when an older posts table does not have it yet.
            $columns = $this->connection->query("PRAGMA table_info(posts)")->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'name');
            if (!in_array('deleted_at', $columnNames, true)) {
                $this->connection->exec("ALTER TABLE posts ADD COLUMN deleted_at DATETIME DEFAULT NULL");
            }

            // Migration for SQLite files created before Sprint 5 RBAC:
            // rename legacy role values to the new role names. The role
            // column is TEXT here, so plain UPDATEs suffice (idempotent).
            $this->connection->exec("UPDATE users SET role = 'system_admin' WHERE role = 'admin'");
            $this->connection->exec("UPDATE users SET role = 'society_admin' WHERE role = 'society'");

            // Migration for SQLite files created before society
            // announcements: add posts.is_announcement when missing.
            $postColumns = $this->connection->query("PRAGMA table_info(posts)")->fetchAll(PDO::FETCH_ASSOC);
            $postColumnNames = array_column($postColumns, 'name');
            if (!in_array('is_announcement', $postColumnNames, true)) {
                $this->connection->exec("ALTER TABLE posts ADD COLUMN is_announcement INTEGER NOT NULL DEFAULT 0");
            }
        }
    }

    public static function getInstance() {
        if(!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function getDriver() {
        return $this->driver;
    }

    /** True when the app is running on the SQLite fallback (MySQL unreachable). */
    public function isFallback() {
        return $this->driver === 'sqlite';
    }
}