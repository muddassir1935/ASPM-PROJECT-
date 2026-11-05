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

    <div>
        <h3 style="font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-bottom: 1rem;">
            Your Bookmarks (<?= count($saved) ?>)
        </h3>

        <?php if (empty($saved)): ?>
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 3rem; text-align: center; box-shadow: var(--shadow-sm);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🔖</div>
                <h4 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 8px;">No saved posts yet</h4>
                <p style="font-size: 0.9rem; color: #64748b; max-width: 320px; margin: 0 auto;">
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
