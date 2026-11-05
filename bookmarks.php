<?php
/**
 * Bookmarks Page (Sprint 3, WBS 3.4.4).
 *
 * Lists the logged-in user's saved posts, most recently saved first.
 * Unsaving a post here removes its card immediately (AJAX).
 */
require_once 'config/Database.php';
require_once 'classes/Post.php';
require_once 'classes/Bookmark.php';
require_once 'classes/Auth.php';
require_once 'includes/social_widgets.php';

// Redirect before any output: header.php below starts sending HTML.
if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';

$db = Database::getInstance()->getConnection();
$postModel = new Post($db);
$bookmarkModel = new Bookmark($db);

$saved = $bookmarkModel->getBookmarkedPosts((int)$currentUser['id'], 50, 0);
foreach ($saved as &$savedPost) {
    $savedPost['time_ago'] = $postModel->formatTimeAgo($savedPost['created_at']);
}
unset($savedPost);
$bookmarkedIds = array_map('intval', array_column($saved, 'id'));
?>

<div class="layout-3col">
    <?php renderLeftNav('bookmarks'); ?>

    <div class="col-main">
        <h3 class="page-title">
            Your Bookmarks (<?= count($saved) ?>)
        </h3>

        <?php if (empty($saved)): ?>
            <div class="empty-state">
                <span class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3.5h10V20l-5-3.8L7 20Z"/></svg></span>
                <h4 class="empty-title">No saved posts yet</h4>
                <p class="empty-text">
                    Tap Save on any post to keep it here for later.
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($saved as $savedPost): ?>
                <?php renderPostCard($savedPost, $currentUser, $bookmarkedIds, true); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="col-side">
        <?php renderWhoToFollowWidget($db, (int)$currentUser['id']); ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
