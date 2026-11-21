<?php
/**
 * Class PostController
 * 
 * Controller layer responsible for handling HTTP requests, user authentication checks,
 * sanitizing inputs, orchestrating the Post model, and returning structured responses.
 */
require_once __DIR__ . '/Post.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Authorization.php';

class PostController {
    private Post $postModel;
    private ?array $currentUser;
    private Authorization $authorization;
    private Auth $auth;

    /**
     * PostController constructor.
     * 
     * @param Post $postModel Injected Post model instance
     * @param array|null $currentUser Optional user array (defaults to $auth->user())
     * @param Authorization|null $authorization Optional injected Authorization instance
     * @param Auth|null $auth Optional injected Auth instance
     */
    public function __construct(
        Post $postModel,
        ?array $currentUser = null,
        ?Authorization $authorization = null,
        ?Auth $auth = null
    ) {
        $this->postModel = $postModel;
        $this->authorization = $authorization ?? new Authorization();
        $this->auth = $auth ?? new Auth($this->authorization);
        $this->currentUser = $currentUser ?? $this->auth->user();
    }

    /**
     * Handle creating a new post.
     *
     * @param string $content Raw content submitted from form/request
     * @param bool $asAnnouncement Requested announcement flag. It is
     *        honoured only for roles holding 'announcement.create'
     *        (society_admin / system_admin); anyone else gets a normal
     *        post, so a crafted request cannot escalate a plain post
     *        into an announcement.
     * @return array Response structure: ['success' => bool, 'message' => string, 'post_id' => ?int]
     */
    public function handleCreate(string $content, bool $asAnnouncement = false): array {
        if (!$this->currentUser) {
            return [
                'success' => false,
                'message' => 'Authentication required. Please log in to post.',
                'post_id' => null
            ];
        }

        // Audit C7: the RBAC matrix is enforced, not just declared.
        if (!$this->can('post.create')) {
            return [
                'success' => false,
                'message' => 'You do not have permission to post.',
                'post_id' => null
            ];
        }

        $validation = $this->postModel->validateContent($content);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['error'],
                'post_id' => null
            ];
        }

        $isAnnouncement = $asAnnouncement
            && $this->authorization->can($this->currentUser['role'] ?? 'student', 'announcement.create');
        $newPostId = $this->postModel->create((int)$this->currentUser['id'], $content, $isAnnouncement);
        if ($newPostId) {
            return [
                'success' => true,
                'message' => $isAnnouncement ? 'Announcement published successfully!' : 'Post published successfully!',
                'post_id' => $newPostId
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to publish post. Please try again.',
            'post_id' => null
        ];
    }

    /**
     * Handle updating an existing post.
     * 
     * @param int $postId
     * @param string $newContent
     * @return array
     */
    public function handleUpdate(int $postId, string $newContent): array {
        if (!$this->currentUser) {
            return [
                'success' => false,
                'message' => 'Authentication required.'
            ];
        }

        if ($postId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid post ID provided.'
            ];
        }

        if (!$this->can('post.edit_own')) {
            return [
                'success' => false,
                'message' => 'You do not have permission to edit posts.'
            ];
        }

        $validation = $this->postModel->validateContent($newContent);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['error']
            ];
        }

        $updated = $this->postModel->update($postId, (int)$this->currentUser['id'], $newContent);
        if ($updated) {
            return [
                'success' => true,
                'message' => 'Post updated successfully!'
            ];
        }

        return [
            'success' => false,
            'message' => 'Permission denied or post not found.'
        ];
    }

    /**
     * Handle deleting a post.
     * 
     * @param int $postId
     * @return array
     */
    public function handleDelete(int $postId): array {
        if (!$this->currentUser) {
            return [
                'success' => false,
                'message' => 'Authentication required.'
            ];
        }

        if ($postId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid post ID provided.'
            ];
        }

        // Audit C7: own deletes need post.delete_own; deleting someone
        // else's post needs post.delete_any, plus announcement.delete_any
        // when that post is an announcement.
        $existing = $this->postModel->getById($postId);
        $isAnnouncement = $existing && !empty($existing['is_announcement']);
        $canDeleteOwn = $this->can('post.delete_own');
        $canDeleteAny = $this->can('post.delete_any')
            && (!$isAnnouncement || $this->can('announcement.delete_any'));
        $deleted = $existing
            ? $this->postModel->delete($postId, (int)$this->currentUser['id'], $canDeleteAny, $canDeleteOwn)
            : false;

        if ($deleted) {
            return [
                'success' => true,
                'message' => 'Post deleted successfully.'
            ];
        }

        return [
            'success' => false,
            'message' => 'Permission denied or post could not be deleted.'
        ];
    }

    /** Does the current user's role hold $permission? */
    private function can(string $permission): bool {
        return $this->authorization->can($this->currentUser['role'] ?? 'student', $permission);
    }

    /**
     * Retrieve paginated timeline feed.
     *
     * @param int $page 1-indexed page number
     * @param int $limit Posts per page
     * @return array
     */
    public function getTimelineFeed(int $page = 1, int $limit = 20): array {
        $viewerId = isset($this->currentUser['id']) ? (int)$this->currentUser['id'] : 0;
        return $this->paginate($page, $limit, $this->postModel->countAll(),
            fn(int $l, int $o) => $this->postModel->getFeed($l, $o, $viewerId));
    }

    /**
     * Retrieve a specific user's posts.
     *
     * @param int $userId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getUserFeed(int $userId, int $page = 1, int $limit = 20): array {
        // Sprint 5 (M4): pass the viewer through so is_liked /
        // is_bookmarked are correct on profile lists — without it every
        // heart on a profile showed empty even for liked posts.
        $viewerId = isset($this->currentUser['id']) ? (int)$this->currentUser['id'] : 0;
        return $this->paginate($page, $limit, $this->postModel->countByUser($userId),
            fn(int $l, int $o) => $this->postModel->getByUserId($userId, $l, $o, $viewerId));
    }

    /**
     * Personalized timeline feed (Sprint 3, WBS 3.2): the viewer's own
     * posts plus posts from users they follow.
     */
    public function getPersonalizedTimelineFeed(int $userId, int $page = 1, int $limit = 20): array {
        return $this->paginate($page, $limit, $this->postModel->countPersonalized($userId),
            fn(int $l, int $o) => $this->postModel->getPersonalizedFeed($userId, $l, $o));
    }

    /**
     * Shared pagination (audit F1). The requested page is clamped to the
     * last page that has posts, so ?page=999 shows the final page with a
     * working "Previous" link instead of a misleading "No posts yet" empty
     * state with no way back.
     */
    private function paginate(int $page, int $limit, int $total, callable $fetch): array {
        $limit = max(1, $limit);
        $lastPage = max(1, (int)ceil($total / $limit));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $limit;
        $posts = $fetch($limit, $offset);

        return [
            'posts' => $posts,
            'total' => $total,
            'page' => $page,
            'has_more' => ($offset + count($posts)) < $total
        ];
    }
}
