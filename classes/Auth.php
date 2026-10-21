<?php
class Auth {
    private static function startSession() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
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
    }

    public static function logout() {
        self::startSession();
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