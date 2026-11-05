<?php
/**
 * Explore / Search Page (Sprint 3, WBS 3.3.4).
 *
 * Search users by name/email and posts by content. With no query it
 * shows the latest posts plus the who-to-follow widget.
 */
require_once 'config/Database.php';
require_once 'classes/User.php';
require_once 'classes/Post.php';
require_once 'classes/Follow.php';
require_once 'classes/Bookmark.php';
require_once 'includes/social_widgets.php';

require_once 'includes/header.php';

$db = Database::getInstance()->getConnection();
$query = trim((string)($_GET['q'] ?? ''));
$viewerId = $currentUser ? (int)$currentUser['id'] : 0;

$userModel = new User($db);
$postModel = new Post($db);
$followModel = new Follow($db);

$foundUsers = [];
$foundPosts = [];
if ($query !== '') {
    $foundUsers = $userModel->searchUsers($query, $viewerId, 20);
    $foundPosts = $postModel->searchPosts($query, 20, 0);
} else {
    $foundPosts = $postModel->getFeed(20, 0);
}

$bookmarkedIds = [];
if ($currentUser) {
    $bookmarkModel = new Bookmark($db);
    $bookmarkedIds = array_map('intval', array_column($bookmarkModel->getBookmarkedPosts($viewerId, 500), 'id'));
}
?>

<div class="layout-3col">
    <?php renderLeftNav('explore'); ?>

    <div>
        <h3 style="font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-bottom: 1rem;">Explore</h3>

        <form method="GET" action="explore.php" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; display: flex; gap: 8px;">
            <input class="form-control" type="text" name="q" placeholder="Search people or posts..." value="<?= htmlspecialchars($query) ?>" style="margin: 0;">
            <button type="submit" style="background: var(--primary); color: white; border: none; padding: 11px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; flex-shrink: 0;">Search</button>
        </form>

        <?php if ($query !== ''): ?>
            <h4 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 0.75rem;">
                People matching "<?= htmlspecialchars($query) ?>"
            </h4>
            <?php if (empty($foundUsers)): ?>
                <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem;">No people found.</p>
            <?php else: ?>
                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.5rem 1.1rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
                    <?php foreach ($foundUsers as $foundUser): ?>
                        <?php
                            $uInitial = strtoupper(mb_substr(trim($foundUser['name']), 0, 1, 'UTF-8'));
                            $uIsFollowing = $currentUser ? $followModel->isFollowing($viewerId, (int)$foundUser['id']) : false;
                        ?>
                        <div class="user-row">
                            <a href="profile.php?id=<?= (int)$foundUser['id'] ?>" class="user-row-link">
                                <span class="avatar-sm"><?= htmlspecialchars($uInitial) ?></span>
                                <span class="user-row-text"><strong><?= htmlspecialchars($foundUser['name']) ?></strong><span><?= htmlspecialchars($foundUser['email']) ?> &bull; <?= (int)$foundUser['follower_count'] ?> followers</span></span>
                            </a>
                            <?php if ($currentUser): ?>
                                <button type="button" class="btn-follow<?= $uIsFollowing ? ' following' : '' ?>" data-follow-toggle data-user-id="<?= (int)$foundUser['id'] ?>"><?= $uIsFollowing ? 'Following' : 'Follow' ?></button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h4 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 0.75rem;">
                Posts matching "<?= htmlspecialchars($query) ?>"
            </h4>
        <?php else: ?>
            <h4 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 0.75rem;">Latest posts</h4>
        <?php endif; ?>

        <?php if (empty($foundPosts)): ?>
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2.5rem; text-align: center; box-shadow: var(--shadow-sm);">
                <p style="font-size: 0.9rem; color: #64748b;">No posts found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($foundPosts as $foundPost): ?>
                <?php renderPostCard($foundPost, $currentUser, $bookmarkedIds); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="col-side">
        <?php if ($currentUser): ?>
            <?php renderWhoToFollowWidget($db, $viewerId); ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
