<?php
/**
 * Notification AJAX & Action Endpoint (Sprint 5, WBS 5.x).
 *
 * Supports:
 * - POST action=mark_read, id=<notif_id> -> marks single notification as read
 * - POST action=mark_all_read -> marks all user's notifications as read
 * - GET action=count -> returns unread notifications count
 *
 * Thin entry point (intent 003, unit 005-controllers-admin, EF-3c):
 * all request handling lives in NotificationController::handle().
 */
require_once 'config/Database.php';
require_once 'classes/NotificationController.php';

$controller = NotificationController::fromConnection(Database::getInstance()->getConnection());
NotificationController::sendJson($controller->handle($_SERVER['REQUEST_METHOD'], $_GET, $_POST));
