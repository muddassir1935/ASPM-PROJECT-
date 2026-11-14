<?php
/**
 * Profile Edit Handler (Sprint 3, WBS 3.3).
 *
 * A logged-in user may only edit their OWN profile: the user id always
 * comes from the session, never from a posted field. Delegates to
 * User::updateProfile, then flashes a message and redirects to the
 * profile page (Post/Redirect/Get pattern).
 *
 * Also handles action=change_password (Sprint 5 final pass): the user
 * supplies the current password plus the new one twice. On success the
 * user's OTHER sessions are revoked (a stolen session must not survive
 * a password change); the current session stays logged in.
 */
require_once 'config/Database.php';
require_once 'classes/User.php';
require_once 'classes/Auth.php';
require_once 'classes/Csrf.php';
require_once 'classes/DbSessionHandler.php';

if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$result = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sprint 5 CSRF: refuse the request when the token is missing or wrong.
    if (!Csrf::validatePost()) {
        $result = ['success' => false, 'message' => 'Security token expired. Please try again.'];
    } else {
        $db = Database::getInstance()->getConnection();
        $userModel = new User($db);
        $currentUser = Auth::user();
        $action = (string)($_POST['action'] ?? 'update_profile');

        if ($action === 'change_password') {
            // Passwords are never trimmed (same rule as login/signup).
            $current = (string)($_POST['current_password'] ?? '');
            $new = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');
            if ($new !== $confirm) {
                $result = ['success' => false, 'message' => 'New passwords do not match.'];
            } elseif (!User::isValidPassword($new)) {
                $result = ['success' => false, 'message' => 'New password must be at least 8 chars, with 1 uppercase, 1 lowercase, 1 number, and 1 special char.'];
            } elseif ($userModel->changePassword((int)$currentUser['id'], $current, $new)) {
                // Revoke every OTHER session; this one stays logged in.
                $handler = new DbSessionHandler($db);
                $handler->destroySessionsForUser((int)$currentUser['id'], session_id());
                $result = ['success' => true, 'message' => 'Password changed. Other sessions have been signed out.'];
            } else {
                $result = ['success' => false, 'message' => 'Current password is incorrect, or the new password is the same as the current one.'];
            }
        } elseif ($action !== 'update_profile') {
            $result = ['success' => false, 'message' => 'Unknown action.'];
        } else {
            $updated = $userModel->updateProfile(
                (int)$currentUser['id'],
                (string)($_POST['name'] ?? ''),
                (string)($_POST['bio'] ?? ''),
                isset($_POST['cover_style']) ? (string)$_POST['cover_style'] : null
            );
            $result = $updated
                ? ['success' => true, 'message' => 'Profile updated!']
                : ['success' => false, 'message' => 'Could not update profile. Name is required and bio must be under 500 characters.'];

            // Keep the header's displayed name in sync after a rename.
            if ($updated) {
                $_SESSION['user_name'] = trim((string)$_POST['name']);
            }
        }
    }
}

$_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
$_SESSION['flash_message'] = $result['message'];

header('Location: profile.php?id=' . (int)Auth::user()['id']);
exit;
