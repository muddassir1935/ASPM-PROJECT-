<?php
/**
 * Logout Handler (Sprint 5: CSRF-protected).
 *
 * Thin entry point (intent 003, unit 003-controllers-auth): delegates
 * logout handling to AuthController::handleLogout().
 */
require_once 'config/Database.php';
require_once 'classes/AuthController.php';

$controller = AuthController::fromConnection(Database::getInstance()->getConnection());
$response = $controller->handleLogout($_SERVER['REQUEST_METHOD'], $_POST);

header('Location: ' . $response['redirect']);
exit;
