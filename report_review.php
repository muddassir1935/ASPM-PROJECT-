<?php
/**
 * Report Review Action Handler (Sprint 5: Admin Moderation Queue)
 *
 * Receives review decisions from the admin panel (dismiss report or
 * action / soft-delete post). Enforces system admin authentication and
 * CSRF token validation before delegating to the Report model.
 */
require_once 'config/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Authorization.php';
require_once 'classes/Csrf.php';
require_once 'classes/Report.php';

if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!Auth::can('report.review')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_message'] = 'You do not have permission to review reports.';
    header('Location: index.php');
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = Auth::user();
$currentUserId = $currentUser ? (int)$currentUser['id'] : 0;
$result = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validatePost()) {
        $result = ['success' => false, 'message' => 'Security token expired. Please try again.'];
    } else {
        $db = Database::getInstance()->getConnection();
        $reportModel = new Report($db);

        $reportId = (int)($_POST['report_id'] ?? 0);
        $action = trim((string)($_POST['action'] ?? ''));

        $result = $reportModel->review($reportId, $action, $currentUserId);
    }
}

$_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
$_SESSION['flash_message'] = $result['message'];

header('Location: admin.php#reports');
exit;
