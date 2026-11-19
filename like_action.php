<?php
/**
 * AJAX Like Toggle (Sprint 4, WBS 4.1).
 *
 * POST post_id -> JSON { success, liked, like_count, message }.
 * Requires a logged-in user and a valid CSRF token (Sprint 5).
 *
 * Thin entry point (intent 003, unit 004-controllers-social): all request
 * handling lives in SocialController::handleLike().
 */
require_once 'config/Database.php';
require_once 'classes/SocialController.php';

$controller = SocialController::fromConnection(Database::getInstance()->getConnection());
SocialController::sendJson($controller->handleLike($_SERVER['REQUEST_METHOD'], $_POST));
