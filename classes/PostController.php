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

    /**
     * PostController constructor.
     * 
     * @param Post $postModel Injected Post model instance
     * @param array|null $currentUser Optional user array (defaults to Auth::user())
     */
    public function __construct(Post $postModel, ?array $currentUser = null) {
        $this->postModel = $postModel;
        $this->currentUser = $currentUser ?? Auth::user();
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

        $validation = $this->postModel->validateContent($content);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['error'],
                'post_id' => null
            ];
        }

        $isAnnouncement = $asAnnouncement
            && Authorization::can($this->currentUser['role'] ?? 'student', 'announcement.create');
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

        $role = $this->currentUser['role'] ?? 'student';
        $canDeleteAny = Authorization::can($role, 'post.delete_any');
        $deleted = $this->postModel->delete($postId, (int)$this->currentUser['id'], $canDeleteAny);

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

    /**
     * Retrieve paginated timeline feed.
     * 
     * @param int $page 1-indexed page number
     * @param int $limit Posts per page
     * @return array
     */
    public function getTimelineFeed(int $page = 1, int $limit = 20): array {
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        $viewerId = isset($this->currentUser['id']) ? (int)$this->currentUser['id'] : 0;
        $posts = $this->postModel->getFeed($limit, $offset, $viewerId);
        $total = $this->postModel->countAll();

        return [
            'posts' => $posts,
            'total' => $total,
            'page' => $page,
            'has_more' => ($offset + count($posts)) < $total
        ];
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
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        // Sprint 5 (M4): pass the viewer through so is_liked /
        // is_bookmarked are correct on profile lists — without it every
        // heart on a profile showed empty even for liked posts.
        $viewerId = isset($this->currentUser['id']) ? (int)$this->currentUser['id'] : 0;
        $posts = $this->postModel->getByUserId($userId, $limit, $offset, $viewerId);
        $total = $this->postModel->countByUser($userId);

        return [
            'posts' => $posts,
            'total' => $total,
            'page' => $page,
            'has_more' => ($offset + count($posts)) < $total
        ];
    }

    /**
     * Personalized timeline feed (Sprint 3, WBS 3.2): the viewer's own
     * posts plus posts from users they follow.
     */
    public function getPersonalizedTimelineFeed(int $userId, int $page = 1, int $limit = 20): array {
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        $posts = $this->postModel->getPersonalizedFeed($userId, $limit, $offset);
        $total = $this->postModel->countPersonalized($userId);

        return [
            'posts' => $posts,
            'total' => $total,
            'page' => $page,
            'has_more' => ($offset + count($posts)) < $total
        ];
    }
}
