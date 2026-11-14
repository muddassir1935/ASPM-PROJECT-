<?php
/**
 * Explore / Search Page (Sprint 3 & 4: WBS 3.3.4, 4.2).
 *
 * Search users by name, posts by content, or filter by hashtag (#tag).
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

// Sprint 5 UI polish (M-12): Explore used to show a fixed 20 posts with
// the rest unreachable. Same Previous/Next pager as the home feed: fetch
// one extra row to detect a next page, then slice it off for display.
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

if ($tagFilter !== '') {
    $foundPosts = $hashtagModel->getPostsByTag($tagFilter, $viewerId, $perPage + 1, $offset);
} elseif ($query !== '') {
    $foundUsers = $userModel->searchUsers($query, $viewerId, 20);
    $foundPosts = $postModel->searchPosts($query, $perPage + 1, $offset, $viewerId);
} else {
    $foundPosts = $postModel->getFeed($perPage + 1, $offset, $viewerId);
}
$hasMore = count($foundPosts) > $perPage;
if ($hasMore) {
    $foundPosts = array_slice($foundPosts, 0, $perPage);
}
// Pager links keep the current search/tag so paging never drops the filter.
$pagerParams = [];
if ($query !== '') {
    $pagerParams['q'] = $query;
}
if ($tagFilter !== '') {
    $pagerParams['tag'] = $tagFilter;
}

$bookmarkedIds = [];
if ($currentUser) {
    $bookmarkModel = new Bookmark($db);
    $bookmarkedIds = array_map('intval', array_column($bookmarkModel->getBookmarkedPosts($viewerId, 500), 'id'));
}
// Sprint 5 final fix (security M3): fetch the viewer's following-set ONCE —
// the people-search loop below used to call isFollowing() per row (N+1).
$viewerFollowingIds = $currentUser
    ? array_flip($followModel->getFollowingIds($viewerId))
    : [];
?>

<div class="layout-3col">
    <?php renderLeftNav('explore'); ?>

    <div class="col-main">
        <div class="explore-header-wrap">
            <form method="GET" action="explore.php" class="explore-search-bar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-ic" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="search" name="q" class="explore-search-input" placeholder="Search CampusConnect, #tags, or people…" value="<?= htmlspecialchars($query !== '' ? $query : ($tagFilter !== '' ? '#' . $tagFilter : '')) ?>" aria-label="Search people, posts, or #tags" autocomplete="off">
                <?php if ($query !== '' || $tagFilter !== ''): ?>
                    <a href="explore.php" class="explore-search-clear" title="Clear search" aria-label="Clear search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </a>
                <?php endif; ?>
                <button type="submit" class="explore-search-btn">Search</button>
            </form>
        </div>

        <?php if ($tagFilter !== ''): ?>
            <div class="explore-filter-banner">
                <div class="explore-filter-badge">
                    <span>Active Tag:</span>
                    <strong>#<?= htmlspecialchars($tagFilter) ?></strong>
                </div>
                <a href="explore.php" class="explore-filter-clear">Clear filter &times;</a>
            </div>
            <h4 class="section-title">
                Posts tagged with #<?= htmlspecialchars($tagFilter) ?>
            </h4>
        <?php elseif ($query !== ''): ?>
            <div class="explore-filter-banner">
                <div class="explore-filter-badge">
                    <span>Search:</span>
                    <strong>"<?= htmlspecialchars($query) ?>"</strong>
                </div>
                <a href="explore.php" class="explore-filter-clear">Clear search &times;</a>
            </div>
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
                            $uIsFollowing = isset($viewerFollowingIds[(int)$foundUser['id']]);
                        ?>
                        <div class="user-row">
                            <a href="profile.php?id=<?= (int)$foundUser['id'] ?>" class="user-row-link">
                                <span class="avatar-sm<?= avatarToneClass((int)$foundUser['id'], (string)($foundUser['role'] ?? '')) ?>"><?= htmlspecialchars($uInitial) ?></span>
                                <span class="user-row-text"><strong><?= htmlspecialchars($foundUser['name']) ?></strong><span><?= htmlspecialchars(Authorization::displayLabel($foundUser['role'] ?? 'student')) ?> &bull; <?= (int)$foundUser['follower_count'] ?> <?= pluralize((int)$foundUser['follower_count'], 'follower') ?></span></span>
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
            <?php /* M-16: same left-aligned .result-note as "No people found."
                   above — the centered .empty-state made the two empty states
                   on this one page disagree. */ ?>
            <p class="result-note">No posts found.</p>
        <?php else: ?>
            <?php foreach ($foundPosts as $foundPost): ?>
                <?php renderPostCard($foundPost, $currentUser, $bookmarkedIds); ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($page > 1 || $hasMore): ?>
            <div class="pager">
                <?php if ($page > 1): ?>
                    <a href="explore.php?<?= htmlspecialchars(http_build_query($pagerParams + ['page' => $page - 1])) ?>">&larr; Previous</a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
                <span class="pager-info">Page <?= (int)$page ?></span>
                <?php if ($hasMore): ?>
                    <a href="explore.php?<?= htmlspecialchars(http_build_query($pagerParams + ['page' => $page + 1])) ?>">Next &rarr;</a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
            </div>
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
