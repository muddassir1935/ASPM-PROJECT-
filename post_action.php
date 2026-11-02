<?php
/**
 * Post Action Handler (Sprint 2: WBS 2.3.3 / 2.3.5 / 2.3.6)
 *
 * Receives POST requests from the compose box, feed cards and the post
 * detail page, delegates all work to PostController, stores the result as
 * a session flash message and redirects (Post/Redirect/Get pattern) so a
 * browser refresh never re-submits a form.
 *
 * Supported POST fields:
 * - action: 'create' | 'update' | 'delete'
 * - content: post text (create/update)
 * - post_id: target post (update/delete)
 * - return:  'index' (default) or a post id to return to post.php?id=<id>
 */
require_once 'config/Database.php';
require_once 'classes/Post.php';
require_once 'classes/PostController.php';
require_once 'classes/Auth.php';

// Every post action requires a logged-in user.
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
    $controller = new PostController(new Post($db));

    $action = $_POST['action'] ?? '';
    $postId = (int)($_POST['post_id'] ?? 0);

    switch ($action) {
        case 'create':
            $result = $controller->handleCreate((string)($_POST['content'] ?? ''));
            break;
        case 'update':
            $result = $controller->handleUpdate($postId, (string)($_POST['content'] ?? ''));
            break;
        case 'delete':
            $result = $controller->handleDelete($postId);
            break;
        default:
            $result = ['success' => false, 'message' => 'Unknown action.'];
            break;
    }
}

// Flash message for the page we redirect to.
$_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
$_SESSION['flash_message'] = $result['message'];

// Safe redirect target: only the feed or a specific post detail page.
$return = $_POST['return'] ?? 'index';
if ($action === 'delete') {
    // A deleted post no longer has a detail page; always go back to the feed.
    header('Location: index.php');
} elseif ($return !== 'index' && ctype_digit((string)$return) && (int)$return > 0) {
    header('Location: post.php?id=' . (int)$return);
} else {
    header('Location: index.php');
}
exit;
