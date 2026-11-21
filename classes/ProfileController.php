<?php
/**
 * Class ProfileController
 *
 * Controller for profile and password actions (intent 003, unit
 * 003-controllers-auth, EF-3a): profile update (name, bio, cover style)
 * and password change (with revocation of the user's OTHER database sessions
 * while keeping the current session signed in).
 *
 * Before this, profile_action.php held all guards, validation, model calls
 * and session revocation inline; it is now a thin entry point that delegates
 * to handleAction() here.
 */
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/DbSessionHandler.php';

class ProfileController extends Model {
    private User $userModel;
    private DbSessionHandler $sessionHandler;
    private ?array $currentUser;
    private Auth $auth;
    private Csrf $csrf;

    public function __construct(
        PDO $db,
        ?User $userModel = null,
        ?DbSessionHandler $sessionHandler = null,
        ?array $currentUser = null,
        ?Auth $auth = null,
        ?Csrf $csrf = null
    ) {
        parent::__construct($db);
        $this->userModel = $userModel ?? new User($db);
        $this->sessionHandler = $sessionHandler ?? new DbSessionHandler($db);
        $this->csrf = $csrf ?? new Csrf();
        $this->auth = $auth ?? new Auth(null, $this->csrf);
        $this->currentUser = $currentUser ?? $this->auth->user();
    }

    /** Build a ProfileController with default models on a PDO connection. */
    public static function fromConnection(
        PDO $db,
        ?array $currentUser = null,
        ?Auth $auth = null,
        ?Csrf $csrf = null
    ): self {
        return new self($db, new User($db), new DbSessionHandler($db), $currentUser, $auth, $csrf);
    }

    /**
     * Dispatch a profile_action.php request (update_profile | change_password).
     *
     * Returns:
     *   ['result' => ?array{success: bool, message: string}, 'redirect' => string]
     * A null 'result' indicates an unauthenticated redirect to login.php with no flash.
     */
    public function handleAction(string $method, array $input, ?string $currentSessionId = null): array {
        if (!$this->currentUser || empty($this->currentUser['id'])) {
            return [
                'result' => null,
                'redirect' => 'login.php'
            ];
        }

        $userId = (int)$this->currentUser['id'];
        $redirect = 'profile.php?id=' . $userId;
        $result = ['success' => false, 'message' => 'Invalid request.'];

        if ($method === 'POST') {
            if (!$this->csrf->validate($input['csrf_token'] ?? null)) {
                $result = ['success' => false, 'message' => 'Security token expired. Please try again.'];
            } else {
                $action = (string)($input['action'] ?? 'update_profile');

                if ($action === 'change_password') {
                    $result = $this->changePassword($userId, $input, $currentSessionId ?? session_id());
                } elseif ($action === 'update_profile') {
                    $result = $this->updateProfile($userId, $input);
                } else {
                    $result = ['success' => false, 'message' => 'Unknown action.'];
                }
            }
        }

        return [
            'result' => $result,
            'redirect' => $redirect
        ];
    }

    /**
     * Update the logged-in user's profile (name, bio, cover_style).
     */
    public function updateProfile(int $userId, array $input): array {
        $name = (string)($input['name'] ?? '');
        $bio = (string)($input['bio'] ?? '');
        $coverStyle = isset($input['cover_style']) ? (string)$input['cover_style'] : null;

        $updated = $this->userModel->updateProfile($userId, $name, $bio, $coverStyle);
        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Could not update profile. Name is required and bio must be under 500 characters.'
            ];
        }

        // Keep the header's displayed name in sync after a rename.
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['user_name'] = User::cleanName($name);
        }

        return [
            'success' => true,
            'message' => 'Profile updated!'
        ];
    }

    /**
     * Change the logged-in user's password and revoke all OTHER sessions.
     */
    public function changePassword(int $userId, array $input, string $currentSessionId = ''): array {
        // Passwords are never trimmed (same rule as login/signup).
        $current = (string)($input['current_password'] ?? '');
        $new = (string)($input['new_password'] ?? '');
        $confirm = (string)($input['confirm_password'] ?? '');

        if ($new !== $confirm) {
            return ['success' => false, 'message' => 'New passwords do not match.'];
        }
        if (!User::isValidPassword($new)) {
            return [
                'success' => false,
                'message' => 'New password must be 8–128 chars, with 1 uppercase, 1 lowercase, 1 number, and 1 special char.'
            ];
        }
        if (!$this->userModel->changePassword($userId, $current, $new)) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect, or the new password is the same as the current one.'
            ];
        }

        // Revoke every OTHER session; the current session stays logged in.
        $this->sessionHandler->destroySessionsForUser($userId, $currentSessionId);

        return [
            'success' => true,
            'message' => 'Password changed. Other sessions have been signed out.'
        ];
    }
}
