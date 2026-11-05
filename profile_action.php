<?php
/**
 * Profile Edit Handler (Sprint 3, WBS 3.3).
 *
 * A logged-in user may only edit their OWN profile: the user id always
 * comes from the session, never from a posted field. Delegates to
 * User::updateProfile, then flashes a message and redirects to the
 * profile page (Post/Redirect/Get pattern).
 */
require_once 'config/Database.php';
require_once 'classes/User.php';
require_once 'classes/Auth.php';

if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$result = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    $userModel = new User($db);
    $currentUser = Auth::user();

    $updated = $userModel->updateProfile(
        (int)$currentUser['id'],
        (string)($_POST['name'] ?? ''),
        (string)($_POST['bio'] ?? '')
    );
    $result = $updated
        ? ['success' => true, 'message' => 'Profile updated!']
        : ['success' => false, 'message' => 'Could not update profile. Name is required and bio must be under 500 characters.'];

    // Keep the header's displayed name in sync after a rename.
    if ($updated) {
        $_SESSION['user_name'] = trim((string)$_POST['name']);
    }
}

$_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
$_SESSION['flash_message'] = $result['message'];

header('Location: profile.php?id=' . (int)Auth::user()['id']);
exit;
