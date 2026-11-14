<?php
/**
 * Admin Action Handler (Sprint 5 RBAC).
 *
 * POST action=assign_role — change a user's role. System admins only
 * (permission 'role.assign'), CSRF-protected. Guards:
 * - the target must exist;
 * - only the three current role names are accepted (User::setRole
 *   enforces this, rejecting legacy 'admin'/'society');
 * - you cannot change your own role;
 * - you cannot demote the last remaining system admin.
 *
 * POST action=reset_password — reset a user's password (Sprint 5 final
 * pass, permission 'user.reset_password'). Generates a temporary
 * password, stores only its Argon2id hash, and shows the plaintext to
 * the admin exactly once (flash message) so they can share it with the
 * user directly — the app sends no email, so there is no mailed link.
 * You cannot reset your own password here (use Change password on your
 * profile); the target's sessions are revoked either way.
 *
 * On success the target's DB sessions are revoked, so the new role /
 * password applies immediately instead of whenever their session
 * happened to refresh.
 */
require_once 'config/Database.php';
require_once 'classes/User.php';
require_once 'classes/Auth.php';
require_once 'classes/Authorization.php';
require_once 'classes/Csrf.php';
require_once 'classes/DbSessionHandler.php';

if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit;
}
// The file serves two admin actions; each checks its own permission
// inside. The gate here only keeps non-admins out of the handler.
if (!Auth::can('user.manage')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_message'] = 'You do not have permission to manage users.';
    header('Location: index.php');
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$result = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validatePost()) {
        $result = ['success' => false, 'message' => 'Security token expired. Please try again.'];
    } else {
        $db = Database::getInstance()->getConnection();
        $userModel = new User($db);
        $currentUser = Auth::user();
        $targetId = (int)($_POST['user_id'] ?? 0);
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'reset_password') {
            if (!Auth::can('user.reset_password')) {
                $result = ['success' => false, 'message' => 'You do not have permission to reset passwords.'];
            } else {
                $target = $targetId > 0 ? $userModel->getById($targetId) : false;
                if ($target === false) {
                    $result = ['success' => false, 'message' => 'User not found.'];
                } elseif ($targetId === (int)$currentUser['id']) {
                    $result = ['success' => false, 'message' => 'Use Change password on your own profile instead.'];
                } else {
                    $tempPassword = User::generateTemporaryPassword();
                    if ($userModel->resetPassword($targetId, $tempPassword)) {
                        $handler = new DbSessionHandler($db);
                        $handler->destroySessionsForUser($targetId);
                        $result = [
                            'success' => true,
                            // Note: admin.php escapes the flash message on output.
                            'message' => 'Temporary password for ' . $target['name'] . ': ' . $tempPassword . ' — share it with them directly; it will not be shown again. Their sessions have been signed out.',
                        ];
                    } else {
                        $result = ['success' => false, 'message' => 'Could not reset the password.'];
                    }
                }
            }
        } elseif ($action !== 'assign_role') {
            $result = ['success' => false, 'message' => 'Unknown action.'];
        } elseif (!Auth::can('role.assign')) {
            $result = ['success' => false, 'message' => 'You do not have permission to manage roles.'];
        } else {
        $newRole = strtolower(trim((string)($_POST['role'] ?? '')));

        $target = $targetId > 0 ? $userModel->getById($targetId) : false;
        if ($target === false) {
            $result = ['success' => false, 'message' => 'User not found.'];
        } elseif ($targetId === (int)$currentUser['id']) {
            $result = ['success' => false, 'message' => 'You cannot change your own role.'];
        } elseif (!Authorization::isValidRole($newRole)) {
            $result = ['success' => false, 'message' => 'Invalid role selected.'];
        } elseif (
            Authorization::normalizeRole($target['role']) === Authorization::ROLE_SYSTEM_ADMIN
            && $newRole !== Authorization::ROLE_SYSTEM_ADMIN
            && $userModel->countSystemAdmins() <= 1
        ) {
            $result = ['success' => false, 'message' => 'Cannot demote the last system admin.'];
        } else {
            $updated = $userModel->setRole($targetId, $newRole);
            if ($updated) {
                // Revoke the target's sessions (no-op on native file sessions).
                $handler = new DbSessionHandler($db);
                $handler->destroySessionsForUser($targetId);
                $result = [
                    'success' => true,
                    // Note: admin.php escapes the flash message on output.
                    'message' => $target['name'] . ' is now ' . Authorization::displayLabel($newRole) . '. Their sessions have been signed out.',
                ];
            } else {
                $result = ['success' => false, 'message' => 'Could not update role.'];
            }
        }
        }
    }
}

$_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
$_SESSION['flash_message'] = $result['message'];

header('Location: admin.php');
exit;
