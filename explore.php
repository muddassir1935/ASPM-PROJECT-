<?php
/**
 * Explore / Search Page (Sprint 3 & 4: WBS 3.3.4, 4.2).
 *
 * Search users by name/email, posts by content, or filter by hashtag (#tag).
 * With no query it shows the latest posts, trending hashtags, and who-to-follow.
 */
require_once 'config/Database.php';
require_once 'classes/User.php';
require_once 'classes/Post.php';
require_once 'classes/Follow.php';
require_once 'classes/Bookmark.php';
require_once 'classes/Hashtag.php';
require_once 'includes/social_widgets.php';

require_once 'includes/header.php';

$db = Database::getInstance()->getConnection();
$query = trim((string)($_GET['q'] ?? ''));
$tagFilter = trim((string)($_GET['tag'] ?? ''));
if (strpos($tagFilter, '#') === 0) {
    $tagFilter = substr($tagFilter, 1);
}

$viewerId = $currentUser ? (int)$currentUser['id'] : 0;

$userModel = new User($db);
$postModel = new Post($db);
$followModel = new Follow($db);
$hashtagModel = new Hashtag($db);

$foundUsers = [];
$foundPosts = [];

if ($tagFilter !== '') {
    $foundPosts = $hashtagModel->getPostsByTag($tagFilter, $viewerId, 50, 0);
} elseif ($query !== '') {
    $foundUsers = $userModel->searchUsers($query, $viewerId, 20);
    $foundPosts = $postModel->searchPosts($query, 20, 0, $viewerId);
} else {
    $foundPosts = $postModel->getFeed(20, 0, $viewerId);
}

$bookmarkedIds = [];
if ($currentUser) {
    $bookmarkModel = new Bookmark($db);
    $bookmarkedIds = array_map('intval', array_column($bookmarkModel->getBookmarkedPosts($viewerId, 500), 'id'));
}
?>

<div class="layout-3col">
    <?php renderLeftNav('explore'); ?>

    <div class="col-main">
        <h3 class="page-title">Explore</h3>

        <form method="GET" action="explore.php" class="search-card search-row">
            <input class="form-control" type="text" name="q" placeholder="Search people, posts, or #tags..." value="<?= htmlspecialchars($query !== '' ? $query : ($tagFilter !== '' ? '#' . $tagFilter : '')) ?>" aria-label="Search people, posts, or #tags">
            <button type="submit" class="btn-primary">Search</button>
        </form>

        <?php if ($tagFilter !== ''): ?>
            <h4 class="section-title">
                Posts tagged with #<?= htmlspecialchars($tagFilter) ?>
            </h4>
        <?php elseif ($query !== ''): ?>
            <h4 class="section-title">
                People matching "<?= htmlspecialchars($query) ?>"
            </h4>
            <?php if (empty($foundUsers)): ?>
                <p class="result-note">No people found.</p>
            <?php else: ?>
                <div class="list-card">
                    <?php foreach ($foundUsers as $foundUser): ?>
                        <?php
                            $uInitial = strtoupper(mb_substr(trim($foundUser['name']), 0, 1, 'UTF-8'));
                            $uIsFollowing = $currentUser ? $followModel->isFollowing($viewerId, (int)$foundUser['id']) : false;
                        ?>
                        <div class="user-row">
                            <a href="profile.php?id=<?= (int)$foundUser['id'] ?>" class="user-row-link">
                                <span class="avatar-sm<?= avatarToneClass((int)$foundUser['id'], (string)($foundUser['role'] ?? '')) ?>"><?= htmlspecialchars($uInitial) ?></span>
                                <span class="user-row-text"><strong><?= htmlspecialchars($foundUser['name']) ?></strong><span><?= htmlspecialchars($foundUser['email']) ?> &bull; <?= (int)$foundUser['follower_count'] ?> followers</span></span>
                            </a>
                            <?php if ($currentUser): ?>
                                <button type="button" class="btn-follow<?= $uIsFollowing ? ' following' : '' ?>" data-follow-toggle data-user-id="<?= (int)$foundUser['id'] ?>"><?= $uIsFollowing ? 'Following' : 'Follow' ?></button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h4 class="section-title">
                Posts matching "<?= htmlspecialchars($query) ?>"
            </h4>
        <?php else: ?>
            <h4 class="section-title">Latest posts</h4>
        <?php endif; ?>

        <?php if (empty($foundPosts)): ?>
            <div class="empty-state">
                <p class="empty-text">No posts found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($foundPosts as $foundPost): ?>
                <?php renderPostCard($foundPost, $currentUser, $bookmarkedIds); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="col-side">
        <?php renderTrendingHashtagsWidget($db); ?>
        <?php if ($currentUser): ?>
            <?php renderWhoToFollowWidget($db, $viewerId); ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
