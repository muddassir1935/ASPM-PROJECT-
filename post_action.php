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
require_once 'classes/Csrf.php';
require_once 'classes/SafeRedirect.php';

$authorization = new Authorization();
$csrf = new Csrf();
$auth = new Auth($authorization, $csrf);

// Every post action requires a logged-in user.
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$result = ['success' => false, 'message' => 'Invalid request.'];
// Defined up front (audit C3): the redirect logic below reads $action even
// when the request is a GET or fails the CSRF check.
$action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sprint 5 CSRF: refuse the request when the token is missing or wrong.
    if (!$csrf->validatePost()) {
        $result = ['success' => false, 'message' => 'Security token expired. Please try again.'];
    } else {
        $db = Database::getInstance()->getConnection();
        $controller = new PostController(new Post($db), null, $authorization, $auth);

        $postId = (int)($_POST['post_id'] ?? 0);

        switch ($action) {
            case 'create':
                $result = $controller->handleCreate(
                    (string)($_POST['content'] ?? ''),
                    !empty($_POST['is_announcement'])
                );
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
}

// Flash message for the page we redirect to.
$_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
$_SESSION['flash_message'] = $result['message'];

// Audit F4: go back to the page the form came from (feed, profile,
// Explore, bookmarks, admin panel, post detail) — validated by
// SafeRedirect so it can only ever be a local page. A deleted post's own
// detail page no longer exists, so that one falls back to the feed.
$deletedId = ($action === 'delete' && !empty($result['success'])) ? (int)($_POST['post_id'] ?? 0) : null;
$safeRedirect = new SafeRedirect();
header('Location: ' . $safeRedirect->target($_POST['return'] ?? 'index', 'index.php', $deletedId));
exit;
