<?php
/**
 * Admin Action Handler (Sprint 5 RBAC).
 *
 * POST action=assign_role — change a user's role.
 * POST action=reset_password — reset a user's password.
 *
 * Thin entry point (intent 003, unit 005-controllers-admin, EF-3c):
 * all request handling lives in AdminController::handleAdminAction().
 */
require_once 'config/Database.php';
require_once 'classes/AdminController.php';

$controller = AdminController::fromConnection(Database::getInstance()->getConnection());
$response = $controller->handleAdminAction($_SERVER['REQUEST_METHOD'], $_POST);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if ($response['result'] !== null) {
    $_SESSION['flash_type'] = $response['result']['success'] ? 'success' : 'error';
    $_SESSION['flash_message'] = $response['result']['message'];
}
header('Location: ' . $response['redirect']);
exit;
