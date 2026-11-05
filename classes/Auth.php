<?php
class Auth {
    private static bool $dbSessionHandlerRegistered = false;
    private static ?DbSessionHandler $dbSessionHandler = null;

    private static function startSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            self::registerDbSessionHandler();
            session_start();
        }
    }

    /**
     * Register the database-backed session handler (Sprint 3, Problem 2)
     * so session data actually lives in the `sessions` table. Any failure
     * here (e.g. the database is unreachable) falls back silently to
     * PHP's native file sessions so login never breaks because of it.
     */
    private static function registerDbSessionHandler(): void {
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

    public static function login($userData) {
        self::startSession();
        // Prevent Session Fixation attack
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_name'] = $userData['name'] ?? 'User';
        $_SESSION['user_email'] = $userData['email'] ?? '';
        $_SESSION['role'] = $userData['role'] ?? 'student';

        // Tag the DB session row with its owner (no-op on native sessions).
        if (self::$dbSessionHandler !== null && session_id() !== '') {
            self::$dbSessionHandler->setUserId(session_id(), (int)$userData['id']);
        }
    }

    public static function logout() {
        self::startSession();
        // If no session could be started (e.g. headers already sent),
        // there is nothing to destroy.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['user_id']);
    }

    public static function user() {
        self::startSession();
        if(self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'] ?? 'User',
                'email' => $_SESSION['user_email'] ?? '',
                'role' => $_SESSION['role'] ?? 'student'
            ];
        }
        return null;
    }
}