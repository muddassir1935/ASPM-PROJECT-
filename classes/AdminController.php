<?php
/**
 * Class AdminController
 *
 * Controller for admin and moderation actions (intent 003, unit 005-controllers-admin,
 * EF-3c): user role assignment, admin password reset, report creation, and report review.
 *
 * Replaces procedural handling in admin_action.php, report_action.php, and report_review.php.
 * Follows the same pattern as SocialController and PostController:
 * models and dependencies are injected, current user defaults to Auth::user(),
 * and request handler methods return structured response arrays for testing without a web server.
 *
 * Response structures:
 *   ['result' => ['success' => bool, 'message' => string] | null, 'redirect' => string]
 * A null result means no flash message is set (e.g. guest redirect to login.php).
 */
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Report.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Authorization.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/DbSessionHandler.php';
require_once __DIR__ . '/NotificationController.php';

class AdminController extends Model {
    private User $userModel;
    private Report $reportModel;
    private DbSessionHandler $sessionHandler;
    private ?array $currentUser;

    public function __construct(PDO $db, ?User $userModel = null, ?Report $reportModel = null,
                                ?DbSessionHandler $sessionHandler = null, ?array $currentUser = null) {
        parent::__construct($db);
        $this->userModel = $userModel ?? new User($db);
        $this->reportModel = $reportModel ?? new Report($db);
        $this->sessionHandler = $sessionHandler ?? new DbSessionHandler($db);
        $this->currentUser = $currentUser ?? Auth::user();
    }

    /** Factory method: construct controller from PDO and optional user */
    public static function fromConnection(PDO $db, ?array $currentUser = null): self {
        return new self($db, null, null, null, $currentUser);
    }

    /**
     * Handle admin actions from admin_action.php (assign_role, reset_password).
     *
     * @param string $method HTTP method (POST required)
     * @param array $input Request payload (usually $_POST)
     * @return array ['result' => ['success' => bool, 'message' => string] | null, 'redirect' => string]
     */
    public function handleAdminAction(string $method, array $input): array {
        if (!$this->currentUser) {
            return ['result' => null, 'redirect' => 'login.php'];
        }

        // Gate: user.manage permission is required to access admin actions
        if (!$this->can('user.manage')) {
            return [
                'result' => ['success' => false, 'message' => 'You do not have permission to manage users.'],
                'redirect' => 'index.php'
            ];
        }

        if ($method !== 'POST') {
            return [
                'result' => ['success' => false, 'message' => 'Invalid request.'],
                'redirect' => 'admin.php'
            ];
        }

        if (!Csrf::validate($input['csrf_token'] ?? null)) {
            return [
                'result' => ['success' => false, 'message' => 'Security token expired. Please try again.'],
                'redirect' => 'admin.php'
            ];
        }

        $action = (string)($input['action'] ?? '');
        $targetId = (int)($input['user_id'] ?? 0);

        if ($action === 'reset_password') {
            $result = $this->resetPassword($targetId);
        } elseif ($action === 'assign_role') {
            $newRole = strtolower(trim((string)($input['role'] ?? '')));
            $result = $this->assignRole($targetId, $newRole);
        } else {
            $result = ['success' => false, 'message' => 'Unknown action.'];
        }

        return [
            'result' => $result,
            'redirect' => 'admin.php'
        ];
    }

    /**
     * Handle user reporting a post from report_action.php.
     *
     * @param string $method HTTP method (POST required)
     * @param array $input Request payload (usually $_POST)
     * @return array ['result' => ['success' => bool, 'message' => string] | null, 'redirect' => string]
     */
    public function handleReport(string $method, array $input): array {
        if (!$this->currentUser) {
            return ['result' => null, 'redirect' => 'login.php'];
        }

        $redirect = $this->resolveReportReturn((string)($input['return'] ?? 'index'));

        if ($method !== 'POST') {
            return [
                'result' => ['success' => false, 'message' => 'Invalid request.'],
                'redirect' => $redirect
            ];
        }

        if (!Csrf::validate($input['csrf_token'] ?? null)) {
            return [
                'result' => ['success' => false, 'message' => 'Security token expired. Please try again.'],
                'redirect' => $redirect
            ];
        }

        if (!$this->can('report.create')) {
            return [
                'result' => ['success' => false, 'message' => 'You do not have permission to report posts.'],
                'redirect' => $redirect
            ];
        }

        $postId = (int)($input['post_id'] ?? 0);
        $reason = trim((string)($input['reason'] ?? ''));
        $detail = isset($input['detail']) ? (string)$input['detail'] : null;

        $result = $this->reportModel->create($this->userId(), $postId, $reason, $detail);

        return [
            'result' => $result,
            'redirect' => $redirect
        ];
    }

    /**
     * Handle admin reviewing a report from report_review.php (dismiss or action/delete).
     *
     * @param string $method HTTP method (POST required)
     * @param array $input Request payload (usually $_POST)
     * @return array ['result' => ['success' => bool, 'message' => string] | null, 'redirect' => string]
     */
    public function handleReportReview(string $method, array $input): array {
        if (!$this->currentUser) {
            return ['result' => null, 'redirect' => 'login.php'];
        }

        if (!$this->can('report.review')) {
            return [
                'result' => ['success' => false, 'message' => 'You do not have permission to review reports.'],
                'redirect' => 'index.php'
            ];
        }

        if ($method !== 'POST') {
            return [
                'result' => ['success' => false, 'message' => 'Invalid request.'],
                'redirect' => 'admin.php#reports'
            ];
        }

        if (!Csrf::validate($input['csrf_token'] ?? null)) {
            return [
                'result' => ['success' => false, 'message' => 'Security token expired. Please try again.'],
                'redirect' => 'admin.php#reports'
            ];
        }

        $reportId = (int)($input['report_id'] ?? 0);
        $action = trim((string)($input['action'] ?? ''));

        $result = $this->reportModel->review($reportId, $action, $this->userId());

        return [
            'result' => $result,
            'redirect' => 'admin.php#reports'
        ];
    }

    /**
     * Forward notification actions to NotificationController.
     */
    public function handleNotification(string $method, array $query, array $input): array {
        $notifController = new NotificationController($this->db, null, $this->currentUser);
        return $notifController->handle($method, $query, $input);
    }

    /**
     * Send flash message and redirect (used by entry point pages).
     */
    public static function sendFlashAndRedirect(array $response): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if ($response['result'] !== null) {
            $_SESSION['flash_type'] = $response['result']['success'] ? 'success' : 'error';
            $_SESSION['flash_message'] = $response['result']['message'];
        }
        header('Location: ' . $response['redirect']);
        exit;
    }

    private function resetPassword(int $targetId): array {
        if (!$this->can('user.reset_password')) {
            return ['success' => false, 'message' => 'You do not have permission to reset passwords.'];
        }

        $target = $targetId > 0 ? $this->userModel->getById($targetId) : false;
        if ($target === false) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        if ($targetId === $this->userId()) {
            return ['success' => false, 'message' => 'Use Change password on your own profile instead.'];
        }

        $tempPassword = User::generateTemporaryPassword();
        if ($this->userModel->resetPassword($targetId, $tempPassword)) {
            $this->sessionHandler->destroySessionsForUser($targetId);
            return [
                'success' => true,
                'message' => 'Temporary password for ' . $target['name'] . ': ' . $tempPassword . ' — share it with them directly; it will not be shown again. Their sessions have been signed out.',
            ];
        }

        return ['success' => false, 'message' => 'Could not reset the password.'];
    }

    private function assignRole(int $targetId, string $newRole): array {
        if (!$this->can('role.assign')) {
            return ['success' => false, 'message' => 'You do not have permission to manage roles.'];
        }

        $target = $targetId > 0 ? $this->userModel->getById($targetId) : false;
        if ($target === false) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        if ($targetId === $this->userId()) {
            return ['success' => false, 'message' => 'You cannot change your own role.'];
        }

        if (!Authorization::isValidRole($newRole)) {
            return ['success' => false, 'message' => 'Invalid role selected.'];
        }

        if (
            Authorization::normalizeRole($target['role']) === Authorization::ROLE_SYSTEM_ADMIN
            && $newRole !== Authorization::ROLE_SYSTEM_ADMIN
            && $this->userModel->countSystemAdmins() <= 1
        ) {
            return ['success' => false, 'message' => 'Cannot demote the last system admin.'];
        }

        $updated = $this->userModel->setRole($targetId, $newRole);
        if ($updated) {
            $this->sessionHandler->destroySessionsForUser($targetId);
            return [
                'success' => true,
                'message' => $target['name'] . ' is now ' . Authorization::displayLabel($newRole) . '. Their sessions have been signed out.',
            ];
        }

        return ['success' => false, 'message' => 'Could not update role.'];
    }

    private function resolveReportReturn(string $return): string {
        $return = trim($return);
        if ($return !== 'index' && preg_match('/^\d+$/', $return) && (int)$return > 0) {
            return 'post.php?id=' . (int)$return;
        }
        if (preg_match('#^(index|explore|bookmarks|notifications|admin)\.php(\?.*)?$#', $return)) {
            return $return;
        }
        if (preg_match('#^(post|profile)\.php\?id=\d+#', $return)) {
            return $return;
        }
        return 'index.php';
    }

    private function userId(): int {
        return (int)($this->currentUser['id'] ?? 0);
    }

    private function can(string $permission): bool {
        return Authorization::can($this->currentUser['role'] ?? 'student', $permission);
    }
}
