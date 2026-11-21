<?php
// Audit C2: one display time zone for every entry point. It used to be set
// only in includes/header.php, so action handlers and CLI scripts ran in
// php.ini's zone. Timestamps are STORED in UTC (see the MySQL time_zone
// below and SQLite's UTC CURRENT_TIMESTAMP) and converted for display by
// Post::parseDbTimestamp() / Post::formatDbDate().
if (!defined('CAMPUSCONNECT_TIMEZONE')) {
    define('CAMPUSCONNECT_TIMEZONE', 'Asia/Karachi');
}
date_default_timezone_set(CAMPUSCONNECT_TIMEZONE);

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
            // Audit C2: read and write TIMESTAMPs in UTC, matching SQLite,
            // whatever time zone the MySQL server itself runs in.
            $this->connection->exec("SET time_zone = '+00:00'");
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

            // Auto-initialize schema if needed (Single source of truth via Unit 010 / EF-7)
            $this->connection->exec("PRAGMA foreign_keys = ON;");
            $sqliteSchemaFile = __DIR__ . '/../db/schema_sqlite.sql';
            if (!file_exists($sqliteSchemaFile)) {
                require_once __DIR__ . '/../scripts/generate_sqlite_schema.php';
                file_put_contents($sqliteSchemaFile, generateSqliteSchema());
            }
            $this->connection->exec(file_get_contents($sqliteSchemaFile));

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