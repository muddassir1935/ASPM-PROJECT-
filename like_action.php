<?php
/**
 * AJAX Like Toggle (Sprint 4, WBS 4.1).
 *
 * POST post_id -> JSON { success, liked, like_count, message }.
 * Requires a logged-in user.
 */
require_once 'config/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Like.php';
require_once 'classes/Cache.php';
require_once 'classes/Csrf.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}

// Sprint 5 CSRF: the footer JS sends csrf_token with every toggle.
if (!Csrf::validatePost()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh and try again.']);
    exit;
}

$currentUser = Auth::user();
$postId = (int)($_POST['post_id'] ?? 0);

if ($postId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post.']);
    exit;
}

$db = Database::getInstance()->getConnection();
$likeModel = new Like($db);
$result = $likeModel->toggle((int)$currentUser['id'], $postId);

if ($result === null) {
    echo json_encode(['success' => false, 'message' => 'Could not update like.']);
    exit;
}

// Invalidate feed cache for fresh counters
Cache::getInstance()->deleteByPrefix('feed_');

echo json_encode([
    'success' => true,
    'liked' => $result['liked'],
    'like_count' => $result['count'],
    'message' => $result['liked'] ? 'Post liked.' : 'Like removed.'
]);

