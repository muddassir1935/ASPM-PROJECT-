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
        try {
            // Attempt MySQL connection with a short timeout
            $this->connection = new PDO("mysql:host={$this->host};dbname={$this->db};charset=utf8mb4", $this->user, $this->pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2
            ]);
            $this->driver = 'mysql';
        } catch(PDOException $e) {
            // Fallback seamlessly to SQLite (Zero-Config mode so it works even if XAMPP is off)
            $sqlitePath = __DIR__ . '/../db/campusconnect.sqlite';
            $this->connection = new PDO("sqlite:" . $sqlitePath);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->driver = 'sqlite';

            // Auto-initialize schema if needed
            $this->connection->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    password_hash TEXT NOT NULL,
                    role TEXT DEFAULT 'student',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ");
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
}