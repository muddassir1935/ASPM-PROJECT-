<?php
/**
 * Notification AJAX & Action Endpoint (Sprint 5, WBS 5.x).
 *
 * Supports:
 * - POST action=mark_read, id=<notif_id> -> marks single notification as read
 * - POST action=mark_all_read -> marks all user's notifications as read
 * - GET action=count -> returns unread notifications count
 *
 * Requires a logged-in user.
 */
require_once 'config/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Notification.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required.']);
    exit;
}

$currentUser = Auth::user();
$userId = (int)$currentUser['id'];
$db = Database::getInstance()->getConnection();
$notifModel = new Notification($db);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'count';
    if ($action === 'count') {
        echo json_encode([
            'success' => true,
            'unread_count' => $notifModel->countUnread($userId)
        ]);
        exit;
    }
}

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid notification ID.']);
            exit;
        }
        $updated = $notifModel->markRead($id, $userId);
        echo json_encode([
            'success' => $updated,
            'unread_count' => $notifModel->countUnread($userId)
        ]);
        exit;
    }

    if ($action === 'mark_all_read') {
        $updated = $notifModel->markAllRead($userId);
        echo json_encode([
            'success' => true,
            'unread_count' => 0,
            'message' => 'All notifications marked as read.'
        ]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $deleted = $notifModel->delete($id, $userId);
        echo json_encode([
            'success' => $deleted,
            'unread_count' => $notifModel->countUnread($userId)
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
