<?php
/**
 * Class DbSessionHandler
 *
 * Database-backed PHP session handler (Sprint 3, approved Problem 2).
 * Session data is stored in the `sessions` table instead of files, so
 * sessions survive across servers and can be listed/revoked per user.
 * Works with both drivers Database supports (MySQL and SQLite): the
 * upsert syntax differs, so it is chosen from the PDO driver name.
 *
 * The `user_id` column is set by Auth::login() after a successful login
 * and is deliberately NOT overwritten by write(): a session row created
 * at login keeps its owner even as its payload changes.
 *
 * Usage: session_set_save_handler(new DbSessionHandler($pdo), true);
 * Callers should also register session_write_close() as a shutdown
 * function so session data is written before the PDO object is
 * destroyed at request shutdown.
 */
class DbSessionHandler implements SessionHandlerInterface {
    private PDO $db;
    private string $driver;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
        $this->driver = (string)$dbConnection->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        $sql = "SELECT data FROM sessions WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string)$row['data'] : '';
    }

    public function write(string $id, string $data): bool {
        $now = time();
        if ($this->driver === 'mysql') {
            $sql = "INSERT INTO sessions (id, data, last_activity)
                    VALUES (:id, :data, :last_activity)
                    ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = VALUES(last_activity)";
        } else {
            // SQLite 3.24+ upsert; keeps any user_id set at login intact.
            $sql = "INSERT INTO sessions (id, data, last_activity)
                    VALUES (:id, :data, :last_activity)
                    ON CONFLICT(id) DO UPDATE SET data = excluded.data, last_activity = excluded.last_activity";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->bindParam(':data', $data, PDO::PARAM_STR);
        $stmt->bindParam(':last_activity', $now, PDO::PARAM_INT);
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("DB session write error: " . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool {
        $sql = "DELETE FROM sessions WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("DB session destroy error: " . $e->getMessage());
            return false;
        }
    }

    public function gc(int $maxLifetime): int|false {
        $threshold = time() - $maxLifetime;
        $sql = "DELETE FROM sessions WHERE last_activity < :threshold";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':threshold', $threshold, PDO::PARAM_INT);
        try {
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("DB session gc error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tag a session row with its owner. Called by Auth::login() right
     * after login; the row may not exist yet (PHP writes the payload at
     * shutdown), so this upserts a placeholder that write() then fills
     * without touching user_id.
     */
    public function setUserId(string $sessionId, int $userId): bool {
        if ($sessionId === '' || $userId <= 0) {
            return false;
        }
        $now = time();
        if ($this->driver === 'mysql') {
            $sql = "INSERT INTO sessions (id, user_id, data, last_activity)
                    VALUES (:id, :user_id, '', :last_activity)
                    ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), last_activity = VALUES(last_activity)";
        } else {
            $sql = "INSERT INTO sessions (id, user_id, data, last_activity)
                    VALUES (:id, :user_id, '', :last_activity)
                    ON CONFLICT(id) DO UPDATE SET user_id = excluded.user_id, last_activity = excluded.last_activity";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $sessionId, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':last_activity', $now, PDO::PARAM_INT);
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("DB session setUserId error: " . $e->getMessage());
            return false;
        }
    }
}
