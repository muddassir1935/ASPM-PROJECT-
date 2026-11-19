<?php
/**
 * Reply Action Handler (Sprint 3, WBS 3.4.2).
 *
 * Receives POST requests from the post detail page and redirects back to
 * the post with a flash message (Post/Redirect/Get pattern).
 *
 * Supported POST fields:
 * - action: 'create' | 'delete'
 * - post_id: parent post (create)
 * - reply_id: target reply (delete)
 * - content: reply text (create)
 *
 * Thin entry point (intent 003, unit 004-controllers-social): all request
 * handling lives in SocialController::handleReply().
 */
require_once 'config/Database.php';
require_once 'classes/SocialController.php';

$controller = SocialController::fromConnection(Database::getInstance()->getConnection());
$response = $controller->handleReply($_SERVER['REQUEST_METHOD'], $_POST);

if ($response['result'] !== null) {
    $_SESSION['flash_type'] = $response['result']['success'] ? 'success' : 'error';
    $_SESSION['flash_message'] = $response['result']['message'];
}
header('Location: ' . $response['redirect']);
exit;
