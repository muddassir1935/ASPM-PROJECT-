<?php
/**
 * Report Action Handler (Sprint 5: Post Reporting)
 *
 * Receives POST requests from post menus and the post detail page to flag
 * policy-violating content. Validates CSRF and permissions, delegates
 * insertion and domain rules to the Report model, sets a flash message,
 * and safely redirects back.
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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = Auth::user();
$currentUserId = $currentUser ? (int)$currentUser['id'] : 0;

$result = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validatePost()) {
        $result = ['success' => false, 'message' => 'Security token expired. Please try again.'];
    } elseif (!Auth::can('report.create')) {
        $result = ['success' => false, 'message' => 'You do not have permission to report posts.'];
    } else {
        $db = Database::getInstance()->getConnection();
        $reportModel = new Report($db);

        $postId = (int)($_POST['post_id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));
        $detail = isset($_POST['detail']) ? (string)$_POST['detail'] : null;

        $result = $reportModel->create($currentUserId, $postId, $reason, $detail);
    }
}

$_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
$_SESSION['flash_message'] = $result['message'];

// Safe return redirection
$return = trim((string)($_POST['return'] ?? 'index'));

if ($return !== 'index' && ctype_digit($return) && (int)$return > 0) {
    header('Location: post.php?id=' . (int)$return);
} elseif (preg_match('#^(index|explore|bookmarks|notifications|admin)\.php(\?.*)?$#', $return, $m)) {
    header('Location: ' . $return);
} elseif (preg_match('#^(post|profile)\.php\?id=\d+#', $return)) {
    header('Location: ' . $return);
} else {
    header('Location: index.php');
}
exit;
