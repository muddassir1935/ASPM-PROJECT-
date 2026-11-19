<?php
/**
 * AJAX Bookmark Toggle (Sprint 3, WBS 3.4.4).
 *
 * POST post_id -> JSON { success, bookmarked, message }.
 * Requires a logged-in user and a valid CSRF token (Sprint 5).
 *
 * Thin entry point (intent 003, unit 004-controllers-social): all request
 * handling lives in SocialController::handleBookmark().
 */
require_once 'config/Database.php';
require_once 'classes/SocialController.php';

$controller = SocialController::fromConnection(Database::getInstance()->getConnection());
SocialController::sendJson($controller->handleBookmark($_SERVER['REQUEST_METHOD'], $_POST));
