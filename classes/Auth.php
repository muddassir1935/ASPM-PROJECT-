<?php
class Auth {
    public static function login($userData) {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['role'] = $userData['role'];
    }

    public static function logout() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }

    public static function isLoggedIn() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }
}