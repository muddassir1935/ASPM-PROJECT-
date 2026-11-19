<?php
/**
 * AJAX Follow Toggle (Sprint 3, WBS 3.2).
 *
 * POST user_id -> JSON { success, following, followers_count, viewer_id, viewer_following_count, message }.
 * Requires a logged-in user and a valid CSRF token (Sprint 5).
 *
 * Thin entry point (intent 003, unit 004-controllers-social): all request
 * handling lives in SocialController::handleFollow().
 */
require_once 'config/Database.php';
require_once 'classes/SocialController.php';

$controller = SocialController::fromConnection(Database::getInstance()->getConnection());
SocialController::sendJson($controller->handleFollow($_SERVER['REQUEST_METHOD'], $_POST));
