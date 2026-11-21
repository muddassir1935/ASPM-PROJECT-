<?php
/**
 * Report Action Handler (Sprint 5: Post Reporting)
 *
 * Receives POST requests from post menus and the post detail page to flag
 * policy-violating content.
 *
 * Thin entry point (intent 003, unit 005-controllers-admin, EF-3c):
 * all request handling lives in AdminController::handleReport().
 */
require_once 'config/Database.php';
require_once 'classes/AdminController.php';

$controller = AdminController::fromConnection(Database::getInstance()->getConnection());
$response = $controller->handleReport($_SERVER['REQUEST_METHOD'], $_POST);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if ($response['result'] !== null) {
    $_SESSION['flash_type'] = $response['result']['success'] ? 'success' : 'error';
    $_SESSION['flash_message'] = $response['result']['message'];
}
header('Location: ' . $response['redirect']);
exit;
