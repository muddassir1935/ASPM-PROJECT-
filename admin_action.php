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
 * On success the target's DB sessions are revoked, so the new role
 * applies immediately on their next request instead of whenever
 * their session happened to refresh.
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
if (!Auth::can('role.assign')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_message'] = 'You do not have permission to manage roles.';
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
    } elseif (($_POST['action'] ?? '') !== 'assign_role') {
        $result = ['success' => false, 'message' => 'Unknown action.'];
    } else {
        $db = Database::getInstance()->getConnection();
        $userModel = new User($db);
        $currentUser = Auth::user();
        $targetId = (int)($_POST['user_id'] ?? 0);
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

$_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
$_SESSION['flash_message'] = $result['message'];

header('Location: admin.php');
exit;
