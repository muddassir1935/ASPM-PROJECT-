<?php
/**
 * Class Csrf — CSRF protection (Sprint 5 security hardening, WBS 5.3.3).
 *
 * One random token per session, stored server-side in $_SESSION and
 * compared with hash_equals(). Every state-changing form carries it as
 * a hidden field (Csrf::field()) and every AJAX POST sends it as
 * `csrf_token` (read from the <meta name="csrf-token"> tag that
 * includes/header.php emits for logged-in users).
 *
 * The token is rotated on login (Auth::login calls Csrf::rotate()) so a
 * token issued before authentication cannot be reused after it.
 * Validation failure is a hard reject: the action handler must refuse
 * the request, never fall through to the state change.
 */
class Csrf {
    private const SESSION_KEY = 'csrf_token';

    private static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            // The app stores sessions in the database (see Auth), and the
            // CSRF token lives in that same session — so the DB handler
            // must be registered before session_start(). Otherwise a POST
            // handler that validates the token before touching Auth (e.g.
            // login.php / signup.php) would read an empty file-based
            // session and reject every valid token.
            $authFile = __DIR__ . '/Auth.php';
            if (file_exists($authFile)) {
                require_once $authFile;
                if (method_exists('Auth', 'registerDbSessionHandler')) {
                    Auth::registerDbSessionHandler();
                }
                // Sprint 5 (H6): same hardened cookie params as Auth.
                if (method_exists('Auth', 'hardenSessionCookie')) {
                    Auth::hardenSessionCookie();
                }
            }
            session_start();
        }
    }

    /** Current session token, creating one if needed. */
    public static function token(): string {
        self::startSession();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /** Issue a fresh token, invalidating the previous one. */
    public static function rotate(): void {
        self::startSession();
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
    }

    /** Hidden <input> carrying the token, for POST forms. */
    public static function field(): string {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Is this submitted token the session token?
     * Empty / non-string / missing tokens are rejected.
     */
    public static function validate($submitted): bool {
        if (!is_string($submitted) || $submitted === '') {
            return false;
        }
        self::startSession();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        $expected = $_SESSION[self::SESSION_KEY] ?? '';
        if (!is_string($expected) || $expected === '') {
            return false;
        }
        return hash_equals($expected, $submitted);
    }

    /** Validate the token from the current POST body. */
    public static function validatePost(): bool {
        return self::validate($_POST['csrf_token'] ?? null);
    }
}
