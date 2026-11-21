<?php
/**
 * Class AuthController
 *
 * Controller for authentication flows (intent 003, unit 003-controllers-auth,
 * EF-3a): login, registration (signup), and logout. Before this, login.php,
 * signup.php and logout.php each held their own request guards, CSRF checks,
 * validation, rate-limiting and redirects inline; they are now thin entry
 * points that delegate here.
 *
 * Extends Model so it participates in the shared PDO domain hierarchy, while
 * accepting injected User and LoginRateLimiter models (or building them via
 * fromConnection()) and returning plain associative arrays so every flow can
 * be unit-tested without a web server.
 */
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/LoginRateLimiter.php';

class AuthController extends Model {
    private User $userModel;
    private LoginRateLimiter $limiter;
    private ?array $currentUser;
    private bool $applyDelay;

    public function __construct(
        PDO $db,
        ?User $userModel = null,
        ?LoginRateLimiter $limiter = null,
        ?array $currentUser = null,
        bool $applyDelay = true
    ) {
        parent::__construct($db);
        $this->userModel = $userModel ?? new User($db);
        $this->limiter = $limiter ?? new LoginRateLimiter($db);
        $this->currentUser = $currentUser;
        $this->applyDelay = $applyDelay;
    }

    /** Build an AuthController with default models on a PDO connection. */
    public static function fromConnection(PDO $db, ?array $currentUser = null, bool $applyDelay = true): self {
        return new self($db, new User($db), new LoginRateLimiter($db), $currentUser, $applyDelay);
    }

    /**
     * Handle login page request (GET / POST).
     *
     * Returns:
     *   ['redirect' => ?string, 'status' => int, 'error' => string, 'success' => string]
     */
    public function handleLogin(string $method, array $post, array $query = []): array {
        if ($this->isAuthenticated()) {
            return [
                'redirect' => 'index.php',
                'status' => 200,
                'error' => '',
                'success' => ''
            ];
        }

        $error = '';
        $success = '';
        $status = 200;

        if (isset($query['registered'])) {
            $success = 'Registration successful! Please login with your credentials.';
        }
        if (isset($query['logged_out'])) {
            $success = 'You have been safely logged out.';
        }

        if ($method === 'POST') {
            if (!Csrf::validate($post['csrf_token'] ?? null)) {
                $error = 'Security token expired. Please try again.';
            } else {
                $email = trim((string)($post['email'] ?? ''));
                // Passwords are never trimmed: leading/trailing whitespace is part of the password.
                $password = (string)($post['password'] ?? '');

                if ($email === '' || $password === '') {
                    $error = 'Please fill in all fields.';
                } elseif (!User::isCampusEmail($email)) {
                    $error = 'Access restricted: Please enter your official FAST-NUCES campus email (@nu.edu.pk).';
                } elseif ($this->limiter->isLocked($email)) {
                    $status = 429;
                    $minutes = (int)ceil($this->limiter->lockRemainingSeconds($email) / 60);
                    $error = "Too many failed login attempts. Please try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . '.';
                } else {
                    $userData = $this->userModel->verifyPassword($email, $password);

                    if ($userData) {
                        $this->limiter->recordSuccess($email);
                        Auth::login($userData);
                        return [
                            'redirect' => 'index.php',
                            'status' => 200,
                            'error' => '',
                            'success' => $success
                        ];
                    }

                    $this->limiter->recordFailure($email);
                    if ($this->limiter->isLocked($email)) {
                        $status = 429;
                        $minutes = (int)ceil($this->limiter->lockRemainingSeconds($email) / 60);
                        $error = "Too many failed login attempts. Please try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . '.';
                    } else {
                        $attempts = $this->limiter->attemptsInWindow($email);
                        if ($this->applyDelay) {
                            usleep(LoginRateLimiter::delayMicrosecondsForAttempts($attempts));
                        }
                        $error = 'Invalid email or password. Please try again.';
                    }
                }
            }
        }

        return [
            'redirect' => null,
            'status' => $status,
            'error' => $error,
            'success' => $success
        ];
    }

    /**
     * Handle registration page request (GET / POST).
     *
     * Returns:
     *   ['redirect' => ?string, 'error' => string]
     */
    public function handleRegister(string $method, array $post): array {
        if ($this->isAuthenticated()) {
            return [
                'redirect' => 'index.php',
                'error' => ''
            ];
        }

        $error = '';

        if ($method === 'POST') {
            if (!Csrf::validate($post['csrf_token'] ?? null)) {
                $error = 'Security token expired. Please try again.';
            } else {
                $name = trim((string)($post['name'] ?? ''));
                $email = trim((string)($post['email'] ?? ''));
                // Passwords are never trimmed (must match login).
                $password = (string)($post['password'] ?? '');

                if ($name === '' || $email === '' || $password === '') {
                    $error = 'All fields are required.';
                } elseif (!User::isCampusEmail($email)) {
                    $error = 'Access restricted: Registration is exclusively for FAST-NUCES campus emails (@nu.edu.pk).';
                } elseif (!User::isValidPassword($password)) {
                    $error = 'Password must be 8–128 chars, with 1 uppercase, 1 lowercase, 1 number, and 1 special char.';
                } elseif ($this->userModel->register($name, $email, $password)) {
                    return [
                        'redirect' => 'login.php?registered=1',
                        'error' => ''
                    ];
                } else {
                    $error = 'Registration failed. Please check your details and try again.';
                }
            }
        }

        return [
            'redirect' => null,
            'error' => $error
        ];
    }

    /**
     * Handle logout request (POST with CSRF token).
     *
     * Returns:
     *   ['redirect' => string, 'logged_out' => bool]
     */
    public function handleLogout(string $method, array $post): array {
        if ($method === 'POST' && Csrf::validate($post['csrf_token'] ?? null)) {
            Auth::logout();
            return [
                'redirect' => 'login.php?logged_out=1',
                'logged_out' => true
            ];
        }

        return [
            'redirect' => 'index.php',
            'logged_out' => false
        ];
    }

    private function isAuthenticated(): bool {
        if ($this->currentUser !== null) {
            return !empty($this->currentUser['id']);
        }
        return (bool)Auth::isLoggedIn();
    }
}
