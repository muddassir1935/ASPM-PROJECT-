<?php
/**
 * Reply Action Handler (Sprint 3, WBS 3.4.2).
 *
 * Receives POST requests from the post detail page, delegates to Reply,
 * stores the result as a session flash message and redirects back to
 * the post (Post/Redirect/Get pattern).
 *
 * Supported POST fields:
 * - action: 'create' | 'delete'
 * - post_id: parent post (create)
 * - reply_id: target reply (delete)
 * - content: reply text (create)
 */
require_once 'config/Database.php';
require_once 'classes/Reply.php';
require_once 'classes/Auth.php';
require_once 'classes/Csrf.php';

// Every reply action requires a logged-in user.
if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$result = ['success' => false, 'message' => 'Invalid request.'];
$returnPostId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sprint 5 CSRF: refuse the request when the token is missing or wrong.
    if (!Csrf::validatePost()) {
        $result = ['success' => false, 'message' => 'Security token expired. Please try again.'];
        $returnPostId = (int)($_POST['return_post_id'] ?? $_POST['post_id'] ?? 0);
    } else {
        $db = Database::getInstance()->getConnection();
        $replyModel = new Reply($db);
        $currentUser = Auth::user();

        $action = $_POST['action'] ?? '';
        $postId = (int)($_POST['post_id'] ?? 0);
        $replyId = (int)($_POST['reply_id'] ?? 0);
        $returnPostId = $postId > 0 ? $postId : (int)($_POST['return_post_id'] ?? 0);

        switch ($action) {
            case 'create':
            if ($postId <= 0) {
                $result = ['success' => false, 'message' => 'Invalid post.'];
                break;
            }
            $validation = $replyModel->validateContent((string)($_POST['content'] ?? ''));
            if (!$validation['valid']) {
                $result = ['success' => false, 'message' => $validation['error']];
                break;
            }
            $newReplyId = $replyModel->create((int)$currentUser['id'], $postId, (string)$_POST['content']);
            $result = $newReplyId
                ? ['success' => true, 'message' => 'Reply posted!']
                : ['success' => false, 'message' => 'Failed to post reply.'];
            break;
        case 'delete':
            if ($replyId <= 0) {
                $result = ['success' => false, 'message' => 'Invalid reply.'];
                break;
            }
            $deleted = $replyModel->delete($replyId, (int)$currentUser['id'], Auth::can('reply.delete_any'));
            $result = $deleted
                ? ['success' => true, 'message' => 'Reply deleted.']
                : ['success' => false, 'message' => 'Permission denied or reply not found.'];
            break;
        default:
            $result = ['success' => false, 'message' => 'Unknown action.'];
            break;
    }
    }
}

$_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
$_SESSION['flash_message'] = $result['message'];

if ($returnPostId > 0) {
    header('Location: post.php?id=' . $returnPostId);
} else {
    header('Location: index.php');
}
exit;
