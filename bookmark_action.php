<?php
/**
 * AJAX Bookmark Toggle (Sprint 3, WBS 3.4.4).
 *
 * POST post_id -> JSON { success, bookmarked, message }.
 * Requires a logged-in user. (CSRF tokens arrive with Sprint 5 hardening.)
 */
require_once 'config/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Bookmark.php';

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

$currentUser = Auth::user();
$postId = (int)($_POST['post_id'] ?? 0);

if ($postId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post.']);
    exit;
}

$db = Database::getInstance()->getConnection();
$bookmarkModel = new Bookmark($db);
$newState = $bookmarkModel->toggle((int)$currentUser['id'], $postId);

if ($newState === null) {
    echo json_encode(['success' => false, 'message' => 'Could not update bookmark.']);
    exit;
}

echo json_encode([
    'success' => true,
    'bookmarked' => $newState,
    'message' => $newState ? 'Post saved.' : 'Bookmark removed.'
]);
