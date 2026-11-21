<?php
/**
 * Class NotificationController
 *
 * Controller for notification actions (intent 003, unit 005-controllers-admin / EF-3c):
 * mark as read, mark all as read, delete, and count notifications.
 *
 * Replaces procedural handling in notification_action.php.
 * Matches SocialController pattern: returns ['status' => int, 'body' => array].
 */
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Csrf.php';

class NotificationController extends Model {
    private Notification $notifModel;
    private ?array $currentUser;

    public function __construct(PDO $db, ?Notification $notifModel = null, ?array $currentUser = null) {
        parent::__construct($db);
        $this->notifModel = $notifModel ?? new Notification($db);
        $this->currentUser = $currentUser ?? Auth::user();
    }

    public static function fromConnection(PDO $db, ?array $currentUser = null): self {
        return new self($db, null, $currentUser);
    }

    /**
     * Handle notification request from notification_action.php.
     *
     * @param string $method HTTP method (GET or POST)
     * @param array $query Query parameters ($_GET)
     * @param array $input Input payload ($_POST)
     * @return array ['status' => int, 'body' => array]
     */
    public function handle(string $method, array $query, array $input): array {
        if (!$this->currentUser) {
            return ['status' => 401, 'body' => ['success' => false, 'message' => 'Login required.']];
        }

        $userId = $this->userId();

        if ($method === 'GET') {
            $action = $query['action'] ?? 'count';
            if ($action === 'count') {
                return [
                    'status' => 200,
                    'body' => [
                        'success' => true,
                        'unread_count' => $this->notifModel->countUnread($userId)
                    ]
                ];
            }
            // Unknown GET action: the pre-refactor page fell through to 405.
            return ['status' => 405, 'body' => ['success' => false, 'message' => 'Method not allowed.']];
        }

        if ($method === 'POST') {
            if (!Csrf::validate($input['csrf_token'] ?? null)) {
                return [
                    'status' => 403,
                    'body' => ['success' => false, 'message' => 'Security token expired. Please refresh and try again.']
                ];
            }

            $action = $input['action'] ?? '';

            if ($action === 'mark_read') {
                $id = (int)($input['id'] ?? 0);
                if ($id <= 0) {
                    return ['status' => 200, 'body' => ['success' => false, 'message' => 'Invalid notification ID.']];
                }
                $updated = $this->notifModel->markRead($id, $userId);
                return [
                    'status' => 200,
                    'body' => [
                        'success' => $updated,
                        'unread_count' => $this->notifModel->countUnread($userId)
                    ]
                ];
            }

            if ($action === 'mark_all_read') {
                $this->notifModel->markAllRead($userId);
                return [
                    'status' => 200,
                    'body' => [
                        'success' => true,
                        'unread_count' => 0,
                        'message' => 'All notifications marked as read.'
                    ]
                ];
            }

            if ($action === 'delete') {
                $id = (int)($input['id'] ?? 0);
                $deleted = $this->notifModel->delete($id, $userId);
                return [
                    'status' => 200,
                    'body' => [
                        'success' => $deleted,
                        'unread_count' => $this->notifModel->countUnread($userId)
                    ]
                ];
            }

            return ['status' => 200, 'body' => ['success' => false, 'message' => 'Unknown action.']];
        }

        return ['status' => 405, 'body' => ['success' => false, 'message' => 'Method not allowed.']];
    }

    /**
     * Send JSON response and exit.
     */
    public static function sendJson(array $response): void {
        header('Content-Type: application/json');
        if ($response['status'] !== 200) {
            http_response_code($response['status']);
        }
        echo json_encode($response['body']);
        exit;
    }

    private function userId(): int {
        return (int)($this->currentUser['id'] ?? 0);
    }
}
