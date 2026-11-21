<?php
require_once __DIR__ . '/Authorization.php';
require_once __DIR__ . '/Csrf.php';

// Audit C3: action handlers (post_action.php, like_action.php, ...) never
// include includes/header.php, which is where display_errors used to be
// switched off — so a PHP warning there printed the server's absolute
// file path to the browser. Every web entry point loads this class, so
// turn browser error output off here too (errors still go to the log).
// CLI runs (tests, scripts) keep their normal error output.
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

/**
 * Class Auth
 *
 * Authentication and session lifecycle service (converted to an instantiable
 * injectable object in Intent 003, unit 006-helpers-objects, EF-4a).
 */
class Auth {
    /** Idle sessions are logged out after 30 minutes of inactivity. */
    public const IDLE_TIMEOUT_SECONDS = 1800;

    private static bool $dbSessionHandlerRegistered = false;
    private static ?DbSessionHandler $dbSessionHandler = null;
    private static bool $roleRefreshed = false;

    private Authorization $authorization;
    private Csrf $csrf;

    public function __construct(?Authorization $authorization = null, ?Csrf $csrf = null) {
        $this->authorization = $authorization ?? new Authorization();
        $this->csrf = $csrf ?? new Csrf($this);
    }

    /**
     * Harden the session cookie before it is issued (Sprint 5, H6):
     * HttpOnly (JavaScript cannot read it), SameSite=Lax (cross-site
     * POSTs don't carry it — CSRF defence in depth), strict mode (no
     * uninitialized session IDs). Secure is left off: the demo runs
     * over http://localhost; enable it on HTTPS deployments.
     * Public so Csrf can call it too — Csrf starts the session itself
     * on POST handlers that validate the token before touching Auth.
     * No-op once a session is active (params are fixed by then).
     */
    public function hardenSessionCookie(): void {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function startSession(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $this->registerDbSessionHandler();
            $this->hardenSessionCookie();
            session_start();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->enforceIdleTimeout();
        }
    }

    /**
     * Log the user out when the session has been idle longer than
     * IDLE_TIMEOUT_SECONDS. The activity stamp is refreshed on every
     * request that reaches this point.
     */
    private function enforceIdleTimeout(): void {
        $lastActivity = isset($_SESSION['last_activity']) ? (int)$_SESSION['last_activity'] : 0;
        if ($lastActivity > 0 && (time() - $lastActivity) > self::IDLE_TIMEOUT_SECONDS) {
            $_SESSION = [];
            session_destroy();
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Register the database-backed session handler (Sprint 3, Problem 2)
     * so session data actually lives in the `sessions` table. Any failure
     * here (e.g. the database is unreachable) falls back silently to
     * PHP's native file sessions so login never breaks because of it.
     *
     * Public so Csrf can call it too: the CSRF token lives in this same
     * session, so a POST handler that validates the token before it
     * touches Auth must still read the DB-backed session — otherwise
     * it would read an empty file-based session and reject every
     * valid token.
     */
    public function registerDbSessionHandler(): void {
        if (self::$dbSessionHandlerRegistered) {
            return;
        }
        self::$dbSessionHandlerRegistered = true;

        try {
            if (!class_exists('Database')) {
                $dbFile = __DIR__ . '/../config/Database.php';
                if (file_exists($dbFile)) {
                    require_once $dbFile;
                }
            }
            if (!class_exists('DbSessionHandler')) {
                $handlerFile = __DIR__ . '/DbSessionHandler.php';
                if (file_exists($handlerFile)) {
                    require_once $handlerFile;
                }
            }
            if (!class_exists('Database') || !class_exists('DbSessionHandler')) {
                return;
            }
            $pdo = Database::getInstance()->getConnection();
            self::$dbSessionHandler = new DbSessionHandler($pdo);
            session_set_save_handler(self::$dbSessionHandler, true);
            // Write session data before objects are destroyed at shutdown.
            register_shutdown_function('session_write_close');
        } catch (Throwable $e) {
            error_log("DB session handler setup failed, using native sessions: " . $e->getMessage());
            self::$dbSessionHandler = null;
        }
    }

    public function login($userData): void {
        $this->startSession();
        // Prevent Session Fixation attack
        session_regenerate_id(true);
        // Sprint 5 CSRF: a token issued before login must not survive it.
        $this->csrf->rotate();
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_name'] = $userData['name'] ?? 'User';
        $_SESSION['user_email'] = $userData['email'] ?? '';
        // Normalize so legacy values ('admin', 'society') never leak into the session.
        $_SESSION['role'] = $this->authorization->normalizeRole($userData['role'] ?? 'student');
        self::$roleRefreshed = true;

        // Tag the DB session row with its owner (no-op on native sessions).
        if (self::$dbSessionHandler !== null && session_id() !== '') {
            self::$dbSessionHandler->setUserId(session_id(), (int)$userData['id']);
        }
    }

    public function logout(): void {
        $this->startSession();
        // If no session could be started (e.g. headers already sent),
        // there is nothing to destroy.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION = [];
        // Audit C23: setcookie() after output only raises "headers already
        // sent" (seen in RbacTest) — the session row is destroyed below either
        // way, so the stale cookie is harmless and gets replaced next request.
        if (ini_get("session.use_cookies") && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public function isLoggedIn(): bool {
        $this->startSession();
        return isset($_SESSION['user_id']);
    }

    public function user(): ?array {
        $this->startSession();
        if ($this->isLoggedIn()) {
            $this->refreshRoleFromDatabase();
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'] ?? 'User',
                'email' => $_SESSION['user_email'] ?? '',
                'role' => $_SESSION['role'] ?? 'student'
            ];
        }
        return null;
    }

    /**
     * Does the logged-in user hold this permission? (RBAC, Sprint 5.)
     * Unknown permissions and logged-out users are denied.
     */
    public function can(string $permission): bool {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        return $this->authorization->can($user['role'] ?? 'student', $permission);
    }

    /**
     * Re-read the user's role from the database once per request so a
     * role change (e.g. by a system admin) takes effect without forcing
     * the user to log out and back in. On any failure the session role
     * is kept, so login never breaks because of this lookup.
     */
    private function refreshRoleFromDatabase(): void {
        if (self::$roleRefreshed || !isset($_SESSION['user_id'])) {
            return;
        }
        self::$roleRefreshed = true;
        try {
            if (!class_exists('Database')) {
                $dbFile = __DIR__ . '/../config/Database.php';
                if (file_exists($dbFile)) {
                    require_once $dbFile;
                }
            }
            if (!class_exists('Database')) {
                return;
            }
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id LIMIT 1");
            $stmt->bindValue(':id', (int)$_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['role'])) {
                $_SESSION['role'] = $this->authorization->normalizeRole((string)$row['role']);
            }
        } catch (Throwable $e) {
            error_log("Role refresh failed, keeping session role: " . $e->getMessage());
        }
    }
}
