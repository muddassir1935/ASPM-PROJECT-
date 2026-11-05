<?php
/**
 * AJAX Follow Toggle (Sprint 3, WBS 3.2).
 *
 * POST user_id -> JSON { success, following, followers_count, message }.
 * Requires a logged-in user. (CSRF tokens arrive with Sprint 5 hardening.)
 */
require_once 'config/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Follow.php';

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
$targetId = (int)($_POST['user_id'] ?? 0);

if ($targetId <= 0 || $targetId === (int)$currentUser['id']) {
    echo json_encode(['success' => false, 'message' => 'Invalid user.']);
    exit;
}

$db = Database::getInstance()->getConnection();
$followModel = new Follow($db);
$newState = $followModel->toggle((int)$currentUser['id'], $targetId);

if ($newState === null) {
    echo json_encode(['success' => false, 'message' => 'Could not update follow.']);
    exit;
}

echo json_encode([
    'success' => true,
    'following' => $newState,
    'followers_count' => $followModel->countFollowers($targetId),
    'message' => $newState ? 'Following.' : 'Unfollowed.'
]);
