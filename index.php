<?php
require_once 'includes/header.php';

// --- Sprint 2: Timeline feed setup (logged-in dashboard only) ---
$feed = ['posts' => [], 'total' => 0, 'page' => 1, 'has_more' => false];
$flashType = $_SESSION['flash_type'] ?? null;
$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_type'], $_SESSION['flash_message']);

if ($currentUser) {
    require_once 'config/Database.php';
    require_once 'classes/Post.php';
    require_once 'classes/PostController.php';
    require_once 'classes/Bookmark.php';
    require_once 'includes/social_widgets.php';

    $db = Database::getInstance()->getConnection();
    $feedPage = max(1, (int)($_GET['page'] ?? 1));
    $currentTab = ($_GET['tab'] ?? 'for-you') === 'following' ? 'following' : 'for-you';
    $feedController = new PostController(new Post($db), $currentUser);
    if ($currentTab === 'following') {
        $feed = $feedController->getPersonalizedTimelineFeed((int)$currentUser['id'], $feedPage, 10);
    } else {
        $feed = $feedController->getTimelineFeed($feedPage, 10);
    }
    $bookmarkModel = new Bookmark($db);
    $bookmarkedIds = array_map('intval', array_column($bookmarkModel->getBookmarkedPosts((int)$currentUser['id'], 500), 'id'));
}
?>

<?php if($currentUser): ?>
    <!-- Logged In Dashboard View -->
    <div>
        <div class="layout-3col">
            <?php renderLeftNav('home'); ?>
            <div class="col-main">
                <div class="feed-header-sticky">
                    <div class="feed-top-bar">Home</div>
                    <div class="feed-tabs">
                        <a href="index.php?tab=for-you" class="feed-tab <?= $currentTab === 'for-you' ? 'active' : '' ?>">
                            <span class="feed-tab-label">For you</span>
                        </a>
                        <a href="index.php?tab=following" class="feed-tab <?= $currentTab === 'following' ? 'active' : '' ?>">
                            <span class="feed-tab-label">Following</span>
                        </a>
                    </div>
                </div>

        <?php if ($flashMessage): ?>
            <div class="alert <?= $flashType === 'success' ? 'alert-success' : 'alert-error' ?>">
                <div><?= htmlspecialchars($flashMessage) ?></div>
            </div>
        <?php endif; ?>

        <!-- Compose Box (WBS 2.3.1) -->
        <div class="compose-card">
            <form method="POST" action="post_action.php" id="compose-form">
                <input type="hidden" name="action" value="create">
                <?= Csrf::field() ?>
                <div class="compose-flex">
                    <span class="avatar-md<?= avatarToneClass((int)$currentUser['id'], (string)$currentUser['role']) ?>"><?= htmlspecialchars(strtoupper(mb_substr(trim($currentUser['name']), 0, 1, 'UTF-8'))) ?></span>
                    <textarea id="compose-content" class="form-control" name="content" rows="2" maxlength="280" placeholder="What's happening on campus?!"></textarea>
                </div>
                <div class="compose-row">
                    <div class="compose-left-tools">
                        <?php if (Auth::can('announcement.create')): ?>
                            <label class="announcement-pill">
                                <input type="checkbox" name="is_announcement" value="1">
                                <span>📢 Announcement</span>
                            </label>
                        <?php endif; ?>
                    </div>
                    <div class="compose-right-tools">
                        <div class="char-count-wrap">
                            <svg class="char-count-ring" width="24" height="24" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9" fill="none" stroke="var(--line)" stroke-width="2"></circle>
                                <circle id="ring-progress" cx="12" cy="12" r="9" fill="none" stroke="var(--accent)" stroke-width="2" stroke-dasharray="56.548" stroke-dashoffset="56.548" stroke-linecap="round"></circle>
                            </svg>
                            <span id="compose-counter" class="char-counter-text" style="display: none;">280</span>
                        </div>
                        <div class="compose-btn-divider"></div>
                        <button type="submit" id="compose-submit" disabled>Post</button>
                    </div>
                </div>
            </form>
        </div>
        <script>
            (function () {
                var textarea = document.getElementById('compose-content');
                var counter = document.getElementById('compose-counter');
                var ring = document.getElementById('ring-progress');
                var submit = document.getElementById('compose-submit');
                var MAX = 280;
                var CIRCLE_LEN = 56.548;

                function refreshCounter() {
                    var len = textarea.value.length;
                    var remaining = MAX - len;
                    var trimLen = textarea.value.trim().length;
                    var invalid = trimLen === 0 || len > MAX;

                    var pct = Math.min(len / MAX, 1);
                    var offset = CIRCLE_LEN - (pct * CIRCLE_LEN);
                    ring.style.strokeDashoffset = offset;

                    if (remaining <= 20 && remaining >= 0) {
                        ring.style.stroke = '#F59E0B';
                        counter.style.color = '#F59E0B';
                        counter.textContent = remaining;
                        counter.style.display = 'inline';
                    } else if (remaining < 0) {
                        ring.style.stroke = '#EF4444';
                        counter.style.color = '#EF4444';
                        counter.textContent = remaining;
                        counter.style.display = 'inline';
                    } else {
                        ring.style.stroke = 'var(--accent)';
                        counter.style.color = 'var(--muted)';
                        counter.textContent = remaining;
                        counter.style.display = len > 0 ? 'inline' : 'none';
                    }

                    submit.disabled = invalid;
                }

                function autoGrow() {
                    textarea.style.height = 'auto';
                    textarea.style.height = Math.max(54, textarea.scrollHeight) + 'px';
                }

                textarea.addEventListener('input', function () {
                    refreshCounter();
                    autoGrow();
                });
                refreshCounter();
            })();
        </script>

        <?php if (empty($feed['posts'])): ?>
            <div class="empty-state">
                <span class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v14H4z"/><path d="M4 13h4.5l1.5 2h4l1.5-2H20"/></svg></span>
                <h4 class="empty-title">No updates yet</h4>
                <p class="empty-text">
                    <?= $currentTab === 'following'
                        ? 'You haven\'t followed anyone yet, or people you follow haven\'t posted. Find people to follow on the <a href="explore.php">Explore page</a>.'
                        : 'No posts yet on CampusConnect. Be the first to share an update!' ?>
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($feed['posts'] as $feedPost): ?>
                <?php
                    $isOwnPost = (int)$feedPost['user_id'] === (int)$currentUser['id'];
                    // Sprint 5 UI polish (M-7): same owner rule as every
                    // other post card — owner gets Edit + Delete, an
                    // admin gets Delete on anyone's post.
                    $canDeleteAny = Auth::can('post.delete_any');
                    $authorInitial = strtoupper(mb_substr(trim($feedPost['author_name']), 0, 1, 'UTF-8'));
                ?>
                <?php $feedIsAnnouncement = !empty($feedPost['is_announcement']); ?>
                <div class="post-card">
                    <span class="avatar-md<?= avatarToneClass((int)$feedPost['user_id'], (string)($feedPost['author_role'] ?? '')) ?>"><?= htmlspecialchars($authorInitial) ?></span>
                    <div class="post-body">
                        <?php if ($feedIsAnnouncement): ?>
                            <div class="announce-line"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10.5v3h2.5L18 19V5L6.5 10.5H4Z"/><path d="M8 14v4.5"/></svg>Announcement</div>
                        <?php endif; ?>
                        <div class="post-card-head">
                            <a class="post-card-author" href="profile.php?id=<?= (int)$feedPost['user_id'] ?>"><?= htmlspecialchars($feedPost['author_name']) ?></a>
                            <?php
                                $feedEmail = $feedPost['author_email'] ?? '';
                                if ($feedEmail) {
                                    $feedHandle = explode('@', $feedEmail)[0];
                                    echo '<span class="post-card-handle">@' . htmlspecialchars($feedHandle) . '</span>';
                                }
                            ?>
                            <span class="post-card-time">&middot; <?= htmlspecialchars($feedPost['time_ago']) ?></span>
                            <?php if ($isOwnPost || $canDeleteAny): ?>
                                <div class="post-menu-wrap">
                                    <button type="button" class="post-menu-btn" data-post-menu-toggle="<?= (int)$feedPost['id'] ?>" aria-label="More options">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
                                    </button>
                                    <div id="post-menu-<?= (int)$feedPost['id'] ?>" class="post-dropdown-menu" style="display:none;">
                                        <?php if ($isOwnPost): ?>
                                            <button type="button" class="post-dropdown-item" onclick="togglePostEdit(<?= (int)$feedPost['id'] ?>); closeAllPostMenus();">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                Edit Post
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($isOwnPost || $canDeleteAny): ?>
                                            <form method="POST" action="post_action.php" onsubmit="return confirm('Delete this post? This cannot be undone.');" style="margin:0;">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="post_id" value="<?= (int)$feedPost['id'] ?>">
                                                <button type="submit" class="post-dropdown-item item-danger">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                    Delete Post
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <p class="post-card-content"><?= Hashtag::renderHashtagLinks(htmlspecialchars($feedPost['content'])) ?></p>

                        <div class="post-card-actions">
                            <a class="action-reply" href="post.php?id=<?= (int)$feedPost['id'] ?>" aria-label="View post and replies"><span class="action-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z"/></svg></span><?= (int)($feedPost['reply_count'] ?? 0) ?></a>
                            <button type="button" class="action-like <?= !empty($feedPost['is_liked']) ? 'is-liked' : '' ?>" data-like-toggle data-post-id="<?= (int)$feedPost['id'] ?>" aria-label="Like post"><span class="action-ic"><svg viewBox="0 0 24 24" fill="<?= !empty($feedPost['is_liked']) ? '#e0245e' : 'none' ?>" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg></span><span class="like-count"><?= (int)($feedPost['like_count'] ?? 0) ?></span></button>
                            <span class="spacer"></span>
                            <button type="button" class="btn-bookmark" data-bookmark-toggle data-post-id="<?= (int)$feedPost['id'] ?>"><?= in_array((int)$feedPost['id'], $bookmarkedIds, true) ? 'Saved' : 'Save' ?></button>
                        </div>

                        <?php if ($isOwnPost): ?>
                            <form id="post-edit-<?= (int)$feedPost['id'] ?>" method="POST" action="post_action.php" class="edit-form" style="display: none;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="post_id" value="<?= (int)$feedPost['id'] ?>">
                                <textarea name="content" class="form-control" rows="3" maxlength="280" required><?= htmlspecialchars($feedPost['content']) ?></textarea>
                                <div class="edit-form-actions">
                                    <button type="submit" class="btn-primary">Save Changes</button>
                                    <button type="button" class="btn-cancel" onclick="togglePostEdit(<?= (int)$feedPost['id'] ?>)">Cancel</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($feed['page'] > 1 || $feed['has_more']): ?>
                <div class="pager">
                    <?php if ($feed['page'] > 1): ?>
                        <a href="index.php?tab=<?= urlencode($currentTab) ?>&page=<?= (int)$feed['page'] - 1 ?>">&larr; Previous</a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                    <span class="pager-info">Page <?= (int)$feed['page'] ?></span>
                    <?php if ($feed['has_more']): ?>
                        <a href="index.php?tab=<?= urlencode($currentTab) ?>&page=<?= (int)$feed['page'] + 1 ?>">Next &rarr;</a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
            </div>

            <div class="col-side">
                <div class="search-pill-wrap">
                    <svg class="search-pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <form method="GET" action="explore.php" style="flex: 1; margin: 0;">
                        <input class="search-pill-input" type="text" name="q" placeholder="Search CampusConnect..." aria-label="Search people or posts">
                    </form>
                </div>
                <?php renderTrendingHashtagsWidget($db); ?>
                <?php renderWhoToFollowWidget($db, (int)$currentUser['id']); ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Public Hero View -->
    <div class="hero">
        <h1 class="hero-title">
            Campus Life,<br>Connected.
        </h1>

        <p class="hero-sub">
            Join the ultimate student network. Share updates, discover university events, and stay connected with your campus community.
        </p>

        <div class="hero-actions">
            <a href="signup.php" class="btn-hero-primary">
                Create Your Account
            </a>
            <a href="login.php" class="btn-hero-ghost">
                Log In
            </a>
        </div>

        <!-- Features Overview Grid -->
        <div class="features-grid">
            <div class="feature-card">
                <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10.5v3h2.5L18 19V5L6.5 10.5H4Z"/><path d="M8 14v4.5"/></svg></span>
                <h4>Campus Announcements</h4>
                <p>
                    Never miss an important update. See announcements from faculty, societies, and administration right in your feed.
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8.5" r="3.2"/><path d="M3.5 19.5c.6-3 2.8-4.5 5.5-4.5s4.9 1.5 5.5 4.5"/><path d="M16 5.6a3.2 3.2 0 0 1 0 5.8"/><path d="M17.5 15.3c1.7.6 2.7 1.9 3 4.2"/></svg></span>
                <h4>Connect with Peers</h4>
                <p>
                    Find and follow students from your department. Share notes, discuss projects, and build your network.
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M10.5 18.5h3"/></svg></span>
                <h4>Always Accessible</h4>
                <p>
                    Experience a fast, responsive design that works perfectly on your laptop, tablet, or smartphone.
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>