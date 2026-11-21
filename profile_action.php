<?php
/**
 * Profile Edit & Password Change Handler (Sprint 3, WBS 3.3; Sprint 5).
 *
 * Thin entry point (intent 003, unit 003-controllers-auth): all request
 * handling, validation and session revocation live in
 * ProfileController::handleAction().
 */
require_once 'config/Database.php';
require_once 'classes/ProfileController.php';

$controller = ProfileController::fromConnection(Database::getInstance()->getConnection());
$response = $controller->handleAction($_SERVER['REQUEST_METHOD'], $_POST);

if ($response['result'] !== null) {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $_SESSION['flash_type'] = $response['result']['success'] ? 'success' : 'error';
    $_SESSION['flash_message'] = $response['result']['message'];
}
header('Location: ' . $response['redirect']);
exit;
