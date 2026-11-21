<?php
/**
 * Class SocialController
 *
 * Controller for the social actions (intent 003, unit 004-controllers-social,
 * EF-3b): like, follow and bookmark toggles (AJAX / JSON) and reply
 * create / delete (Post/Redirect/Get). Before this, each *_action.php page
 * held its own guards, validation and response building; the pages are now
 * thin entry points that call one method here and send the result.
 *
 * Same pattern as PostController: models are injected, the current user
 * defaults to $auth->user(), and every method returns a plain array so it can
 * be tested without a web server. Response bodies are byte-for-byte the
 * ones the pages produced before the refactor.
 *
 * AJAX methods return ['status' => int HTTP code, 'body' => JSON array].
 * The reply method returns ['result' => ['success', 'message'] | null,
 * 'redirect' => string]; a null result means "no flash message" (used for
 * the logged-out redirect to login.php).
 */
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Authorization.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/Like.php';
require_once __DIR__ . '/Follow.php';
require_once __DIR__ . '/Bookmark.php';
require_once __DIR__ . '/Reply.php';

class SocialController {
    private Like $likeModel;
    private Follow $followModel;
    private Bookmark $bookmarkModel;
    private Reply $replyModel;
    private ?array $currentUser;
    private Authorization $authorization;
    private Csrf $csrf;
    private Auth $auth;

    public function __construct(
        Like $likeModel,
        Follow $followModel,
        Bookmark $bookmarkModel,
        Reply $replyModel,
        ?array $currentUser = null,
        ?Authorization $authorization = null,
        ?Csrf $csrf = null,
        ?Auth $auth = null
    ) {
        $this->likeModel = $likeModel;
        $this->followModel = $followModel;
        $this->bookmarkModel = $bookmarkModel;
        $this->replyModel = $replyModel;
        $this->authorization = $authorization ?? new Authorization();
        $this->csrf = $csrf ?? new Csrf();
        $this->auth = $auth ?? new Auth($this->authorization, $this->csrf);
        $this->currentUser = $currentUser ?? $this->auth->user();
    }

    /** Build a controller with all four models on one PDO connection. */
    public static function fromConnection(
        PDO $db,
        ?array $currentUser = null,
        ?Authorization $authorization = null,
        ?Csrf $csrf = null,
        ?Auth $auth = null
    ): self {
        return new self(
            new Like($db),
            new Follow($db),
            new Bookmark($db),
            new Reply($db),
            $currentUser,
            $authorization,
            $csrf,
            $auth
        );
    }

    /**
     * Like toggle (Sprint 4, WBS 4.1).
     * POST post_id -> { success, liked, like_count, message }.
     */
    public function handleLike(string $method, array $input): array {
        if ($denied = $this->ajaxGuard($method, $input)) {
            return $denied;
        }
        $postId = (int)($input['post_id'] ?? 0);
        if ($postId <= 0) {
            return $this->json(['success' => false, 'message' => 'Invalid post.']);
        }

        $result = $this->likeModel->toggle($this->userId(), $postId);
        if ($result === null) {
            return $this->json(['success' => false, 'message' => 'Could not update like.']);
        }

        // No feed-cache flush needed (audit C9): like counts and is_liked are
        // attached live to cached feed rows, never stored in them.
        return $this->json([
            'success' => true,
            'liked' => $result['liked'],
            'like_count' => $result['count'],
            'message' => $result['liked'] ? 'Post liked.' : 'Like removed.'
        ]);
    }

    /**
     * Follow toggle (Sprint 3, WBS 3.2).
     * POST user_id -> { success, following, followers_count, viewer_id,
     * viewer_following_count, message }.
     */
    public function handleFollow(string $method, array $input): array {
        if ($denied = $this->ajaxGuard($method, $input)) {
            return $denied;
        }
        $viewerId = $this->userId();
        $targetId = (int)($input['user_id'] ?? 0);
        if ($targetId <= 0 || $targetId === $viewerId) {
            return $this->json(['success' => false, 'message' => 'Invalid user.']);
        }

        $newState = $this->followModel->toggle($viewerId, $targetId);
        if ($newState === null) {
            return $this->json(['success' => false, 'message' => 'Could not update follow.']);
        }

        return $this->json([
            'success' => true,
            'following' => $newState,
            'followers_count' => $this->followModel->countFollowers($targetId),
            // Audit F7: the viewer's own Following count changes too.
            'viewer_id' => $viewerId,
            'viewer_following_count' => $this->followModel->countFollowing($viewerId),
            'message' => $newState ? 'Following.' : 'Unfollowed.'
        ]);
    }

    /**
     * Bookmark toggle (Sprint 3, WBS 3.4.4).
     * POST post_id -> { success, bookmarked, message }.
     */
    public function handleBookmark(string $method, array $input): array {
        if ($denied = $this->ajaxGuard($method, $input)) {
            return $denied;
        }
        $postId = (int)($input['post_id'] ?? 0);
        if ($postId <= 0) {
            return $this->json(['success' => false, 'message' => 'Invalid post.']);
        }

        $newState = $this->bookmarkModel->toggle($this->userId(), $postId);
        if ($newState === null) {
            return $this->json(['success' => false, 'message' => 'Could not update bookmark.']);
        }

        return $this->json([
            'success' => true,
            'bookmarked' => $newState,
            'message' => $newState ? 'Post saved.' : 'Bookmark removed.'
        ]);
    }

    /**
     * Reply create / delete (Sprint 3, WBS 3.4.2), Post/Redirect/Get.
     *
     * POST fields: action ('create' | 'delete'), post_id (create),
     * reply_id (delete), content (create), return_post_id (optional).
     */
    public function handleReply(string $method, array $input): array {
        if (!$this->currentUser) {
            return ['result' => null, 'redirect' => 'login.php'];
        }

        $result = ['success' => false, 'message' => 'Invalid request.'];
        $returnPostId = 0;

        if ($method === 'POST') {
            if (!$this->csrf->validate($input['csrf_token'] ?? null)) {
                // Sprint 5 CSRF: refuse the request when the token is missing or wrong.
                $result = ['success' => false, 'message' => 'Security token expired. Please try again.'];
                $returnPostId = (int)($input['return_post_id'] ?? $input['post_id'] ?? 0);
            } else {
                $postId = (int)($input['post_id'] ?? 0);
                $returnPostId = $postId > 0 ? $postId : (int)($input['return_post_id'] ?? 0);
                $action = $input['action'] ?? '';

                switch ($action) {
                    case 'create':
                        $result = $this->createReply($postId, (string)($input['content'] ?? ''));
                        break;
                    case 'delete':
                        $result = $this->deleteReply((int)($input['reply_id'] ?? 0));
                        break;
                    default:
                        $result = ['success' => false, 'message' => 'Unknown action.'];
                        break;
                }
            }
        }

        return [
            'result' => $result,
            'redirect' => $returnPostId > 0 ? 'post.php?id=' . $returnPostId : 'index.php'
        ];
    }

    private function createReply(int $postId, string $content): array {
        // Audit C7: enforce the declared permission.
        if (!$this->can('reply.create')) {
            return ['success' => false, 'message' => 'You do not have permission to reply.'];
        }
        if ($postId <= 0) {
            return ['success' => false, 'message' => 'Invalid post.'];
        }
        $validation = $this->replyModel->validateContent($content);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }
        $newReplyId = $this->replyModel->create($this->userId(), $postId, $content);
        return $newReplyId
            ? ['success' => true, 'message' => 'Reply posted!']
            : ['success' => false, 'message' => 'Failed to post reply.'];
    }

    private function deleteReply(int $replyId): array {
        if ($replyId <= 0) {
            return ['success' => false, 'message' => 'Invalid reply.'];
        }
        $deleted = $this->replyModel->delete($replyId, $this->userId(),
            $this->can('reply.delete_any'), $this->can('reply.delete_own'));
        return $deleted
            ? ['success' => true, 'message' => 'Reply deleted.']
            : ['success' => false, 'message' => 'Permission denied or reply not found.'];
    }

    /**
     * Shared AJAX guards, in the order the pages always applied them:
     * login (401), POST only (405), CSRF token (403). Null = allowed.
     */
    private function ajaxGuard(string $method, array $input): ?array {
        if (!$this->currentUser) {
            return $this->json(['success' => false, 'message' => 'Login required.'], 401);
        }
        if ($method !== 'POST') {
            return $this->json(['success' => false, 'message' => 'POST required.'], 405);
        }
        // Sprint 5 CSRF: the footer JS sends csrf_token with every toggle.
        if (!$this->csrf->validate($input['csrf_token'] ?? null)) {
            return $this->json(['success' => false, 'message' => 'Security token expired. Please refresh and try again.'], 403);
        }
        return null;
    }

    /**
     * Send an AJAX result from handleLike / handleFollow / handleBookmark:
     * JSON content type, the HTTP status, and the encoded body.
     */
    public static function sendJson(array $response): void {
        header('Content-Type: application/json');
        if ($response['status'] !== 200) {
            http_response_code($response['status']);
        }
        echo json_encode($response['body']);
    }

    private function json(array $body, int $status = 200): array {
        return ['status' => $status, 'body' => $body];
    }

    private function userId(): int {
        return (int)$this->currentUser['id'];
    }

    /** Does the current user's role hold $permission? */
    private function can(string $permission): bool {
        return $this->authorization->can($this->currentUser['role'] ?? 'student', $permission);
    }
}
