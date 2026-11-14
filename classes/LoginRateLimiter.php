<?php
/**
 * Class LoginRateLimiter — login brute-force protection (Sprint 5).
 *
 * Failed logins are counted per (email, IP) pair in the
 * `login_attempts` table. After MAX_ATTEMPTS failures inside WINDOW
 * seconds the pair is locked for LOCK_SECONDS; the lock message never
 * reveals whether the email exists. A successful login clears the
 * pair's record. Keying on email+IP (not email alone) means an
 * attacker cannot lock a victim's account out from another machine.
 *
 * $now is injectable (defaults to time()) so tests can move the clock
 * past a lock without sleeping.
 */
class LoginRateLimiter {
    public const MAX_ATTEMPTS = 5;
    public const WINDOW_SECONDS = 900;   // 15 minutes
    public const LOCK_SECONDS = 900;     // 15 minutes

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->ensureTable();
    }

    /**
     * Create the table when it does not exist yet (same statement works
     * on MySQL and SQLite), so login never breaks on an install where
     * the Sprint 5 migration was not run. Any failure here only logs:
     * the limiter then fails open rather than blocking logins.
     */
    private function ensureTable(): void {
        try {
            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS login_attempts (
                    email VARCHAR(255) NOT NULL,
                    ip VARCHAR(45) NOT NULL,
                    attempts INT NOT NULL DEFAULT 0,
                    last_attempt INT NOT NULL DEFAULT 0,
                    locked_until INT NOT NULL DEFAULT 0,
                    PRIMARY KEY (email, ip)
                )"
            );
        } catch (PDOException $e) {
            error_log("LoginRateLimiter table setup failed: " . $e->getMessage());
        }
    }

    private function clientIp(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return is_string($ip) && $ip !== '' ? substr($ip, 0, 45) : 'unknown';
    }

    private function key(string $email, ?string $ip): array {
        return [strtolower(trim($email)), $ip ?? $this->clientIp()];
    }

    /** Is this (email, IP) pair currently locked out? */
    public function isLocked(string $email, ?string $ip = null, ?int $now = null): bool {
        $now = $now ?? time();
        [$cleanEmail, $cleanIp] = $this->key($email, $ip);
        $stmt = $this->db->prepare(
            "SELECT locked_until FROM login_attempts WHERE email = :email AND ip = :ip LIMIT 1"
        );
        $stmt->execute([':email' => $cleanEmail, ':ip' => $cleanIp]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false && (int)($row['locked_until'] ?? 0) > $now;
    }

    /** Seconds remaining on the lock (0 when not locked). */
    public function lockRemainingSeconds(string $email, ?string $ip = null, ?int $now = null): int {
        $now = $now ?? time();
        [$cleanEmail, $cleanIp] = $this->key($email, $ip);
        $stmt = $this->db->prepare(
            "SELECT locked_until FROM login_attempts WHERE email = :email AND ip = :ip LIMIT 1"
        );
        $stmt->execute([':email' => $cleanEmail, ':ip' => $cleanIp]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return 0;
        }
        return max(0, (int)($row['locked_until'] ?? 0) - $now);
    }

    /** Record a failed login; locks the pair once MAX_ATTEMPTS is hit. */
    public function recordFailure(string $email, ?string $ip = null, ?int $now = null): void {
        $now = $now ?? time();
        [$cleanEmail, $cleanIp] = $this->key($email, $ip);
        $stmt = $this->db->prepare(
            "SELECT attempts, last_attempt FROM login_attempts WHERE email = :email AND ip = :ip LIMIT 1"
        );
        $stmt->execute([':email' => $cleanEmail, ':ip' => $cleanIp]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $attempts = 1;
        if ($row !== false) {
            // Failures older than the window start a fresh count.
            $attempts = ((int)$row['last_attempt'] >= $now - self::WINDOW_SECONDS)
                ? (int)$row['attempts'] + 1
                : 1;
        }
        $lockedUntil = $attempts >= self::MAX_ATTEMPTS ? $now + self::LOCK_SECONDS : 0;

        if ($row === false) {
            $upsert = $this->db->prepare(
                "INSERT INTO login_attempts (email, ip, attempts, last_attempt, locked_until)
                 VALUES (:email, :ip, :attempts, :last_attempt, :locked_until)"
            );
        } else {
            $upsert = $this->db->prepare(
                "UPDATE login_attempts SET attempts = :attempts, last_attempt = :last_attempt,
                 locked_until = :locked_until WHERE email = :email AND ip = :ip"
            );
        }
        $upsert->execute([
            ':email' => $cleanEmail,
            ':ip' => $cleanIp,
            ':attempts' => $attempts,
            ':last_attempt' => $now,
            ':locked_until' => $lockedUntil,
        ]);
    }

    /** Record a successful login: the pair's failure history is wiped. */
    public function recordSuccess(string $email, ?string $ip = null): void {
        [$cleanEmail, $cleanIp] = $this->key($email, $ip);
        $stmt = $this->db->prepare(
            "DELETE FROM login_attempts WHERE email = :email AND ip = :ip"
        );
        $stmt->execute([':email' => $cleanEmail, ':ip' => $cleanIp]);
    }

    /** Failures counted inside the current window (0 when none / expired). */
    public function attemptsInWindow(string $email, ?string $ip = null, ?int $now = null): int {
        $now = $now ?? time();
        [$cleanEmail, $cleanIp] = $this->key($email, $ip);
        $stmt = $this->db->prepare(
            "SELECT attempts, last_attempt FROM login_attempts WHERE email = :email AND ip = :ip LIMIT 1"
        );
        $stmt->execute([':email' => $cleanEmail, ':ip' => $cleanIp]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || (int)$row['last_attempt'] < $now - self::WINDOW_SECONDS) {
            return 0;
        }
        return (int)$row['attempts'];
    }
}
