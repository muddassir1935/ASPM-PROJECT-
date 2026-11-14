<?php
/**
 * Profile Page (Sprint 3, WBS 3.3).
 *
 * Shows a user's header (name, bio, role, follower/following counts),
 * an edit form on your own profile, a follow button on other profiles,
 * follower/following modals, and the user's posts.
 */
require_once 'config/Database.php';
require_once 'classes/User.php';
require_once 'classes/Post.php';
require_once 'classes/PostController.php';
require_once 'classes/Follow.php';
require_once 'classes/Bookmark.php';
require_once 'classes/Like.php';
require_once 'includes/social_widgets.php';

require_once 'includes/header.php';

$profileId = (int)($_GET['id'] ?? ($currentUser['id'] ?? 0));
$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$profile = $profileId > 0 ? $userModel->getById($profileId) : false;

$flashType = $_SESSION['flash_type'] ?? null;
$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_type'], $_SESSION['flash_message']);

$isOwnProfile = $profile && $currentUser && (int)$profile['id'] === (int)$currentUser['id'];
$isFollowing = false;
$followers = [];
$following = [];
$feed = ['posts' => [], 'total' => 0, 'page' => 1, 'has_more' => false];
$bookmarkedIds = [];
// Sprint 5 final fix (L9): the Likes tab is the owner's private list —
// anyone could open ?tab=likes on any profile. Non-owners fall back to
// the Posts tab (the tab link is hidden for them below too).
$activeTab = ($_GET['tab'] ?? 'posts') === 'likes' && $isOwnProfile ? 'likes' : 'posts';
$likesTotal = 0;
$likedPosts = [];

if ($profile) {
    $followModel = new Follow($db);
    $likeModel = new Like($db);
    $followers = $followModel->getFollowers((int)$profile['id'], 100);
    $following = $followModel->getFollowing((int)$profile['id'], 100);
    $likesTotal = $likeModel->countByUser((int)$profile['id']);
    // Sprint 5 final fix (security M3): fetch the viewer's following-set
    // ONCE — the modals below used to call isFollowing() per row (N+1).
    $viewerFollowingIds = $currentUser
        ? array_flip($followModel->getFollowingIds((int)$currentUser['id']))
        : [];

    if ($currentUser && !$isOwnProfile) {
        $isFollowing = $followModel->isFollowing((int)$currentUser['id'], (int)$profile['id']);
    }
    $feedPage = max(1, (int)($_GET['page'] ?? 1));
    $feedController = new PostController(new Post($db), $currentUser);
    $feed = $feedController->getUserFeed((int)$profile['id'], $feedPage, 10);

    if ($activeTab === 'likes') {
        $likedPosts = $likeModel->getLikedPosts((int)$profile['id'], 50, 0, $currentUser ? (int)$currentUser['id'] : 0);
    }

    if ($currentUser) {
        $bookmarkModel = new Bookmark($db);
        $bookmarkedIds = array_column($bookmarkModel->getBookmarkedPosts((int)$currentUser['id'], 500), 'id');
        $bookmarkedIds = array_map('intval', $bookmarkedIds);
    }
}
?>

<?php if (!$profile): ?>
    <div class="narrow-wrap">
        <div class="empty-state">
            <span class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg></span>
            <h4 class="empty-title">User not found</h4>
            <p class="empty-text">This profile may not exist, or the link is incorrect.</p>
        </div>
    </div>
<?php else: ?>
    <?php
        $profileInitial = strtoupper(mb_substr(trim($profile['name']), 0, 1, 'UTF-8'));
        $followerCount = (int)$profile['follower_count'];
        $followingCount = (int)$profile['following_count'];
        $coverStyle = User::normalizeCoverStyle($profile['cover_style'] ?? 'signature');
    ?>
    <div class="layout-3col">
        <?php renderLeftNav('profile'); ?>

        <div class="col-main">
            <?php if ($flashMessage): ?>
                <div class="alert <?= $flashType === 'success' ? 'alert-success' : 'alert-error' ?>">
                    <div><?= htmlspecialchars($flashMessage) ?></div>
                </div>
            <?php endif; ?>

            <!-- Sticky Top Profile Bar -->
            <div class="profile-sticky-bar">
                <a href="index.php" class="profile-back-btn" aria-label="Back">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="profile-sticky-meta">
                    <h2 class="profile-sticky-name"><?= htmlspecialchars($profile['name']) ?></h2>
                    <span class="profile-sticky-sub"><?= (int)$feed['total'] ?> <?= pluralize((int)$feed['total'], 'post') ?></span>
                </div>
            </div>

            <!-- Profile header card -->
            <?php $profileIsAdmin = Authorization::isOfficialRole($profile['role'] ?? ''); ?>
            <div class="profile-card">
                <div class="profile-cover cover-<?= htmlspecialchars($coverStyle) ?>" aria-hidden="true"></div>
                <div class="profile-hero-row">
                    <span class="avatar-lg<?= avatarToneClass((int)$profile['id'], (string)($profile['role'] ?? '')) ?>"><?= htmlspecialchars($profileInitial) ?></span>
                    <div class="profile-action">
                        <?php if ($isOwnProfile): ?>
                            <button type="button" class="profile-edit-btn" onclick="toggleModal('edit-profile-modal')">Edit Profile</button>
                        <?php elseif ($currentUser): ?>
                            <button type="button" class="btn-follow<?= $isFollowing ? ' following' : '' ?>" data-follow-toggle data-user-id="<?= (int)$profile['id'] ?>"><?= $isFollowing ? 'Following' : 'Follow' ?></button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-details">
                    <div class="profile-name-row">
                        <h1 class="profile-display-name"><?= htmlspecialchars($profile['name']) ?></h1>
                        <?php if (($profile['role'] ?? 'student') !== 'student'): ?>
                            <span class="role-badge role-badge-<?= htmlspecialchars($profile['role'] ?? '') ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                <?= htmlspecialchars(Authorization::displayLabel($profile['role'] ?? '')) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php
                        $emailPrefix = explode('@', $profile['email'] ?? '')[0];
                    ?>
                    <div class="profile-handle">@<?= htmlspecialchars($emailPrefix) ?></div>

                    <?php if (!empty($profile['bio'])): ?>
                        <p class="profile-bio"><?= htmlspecialchars($profile['bio']) ?></p>
                    <?php endif; ?>

                    <div class="profile-metadata-row">
                        <?php if (!empty($profile['created_at'])): ?>
                            <span class="profile-meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                Joined <?= date('F Y', strtotime($profile['created_at'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="profile-stats-row">
                        <button type="button" class="stat-btn" onclick="toggleModal('following-modal')">
                            <strong><?= $followingCount ?></strong>
                            <span>Following</span>
                        </button>
                        <button type="button" class="stat-btn" onclick="toggleModal('followers-modal')">
                            <strong data-followers-count-for="<?= (int)$profile['id'] ?>"><?= $followerCount ?></strong>
                            <span><?= pluralize($followerCount, 'Follower') ?></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Profile Tabs (Posts / Likes) -->
            <div class="profile-tabs-header">
                <a href="profile.php?id=<?= (int)$profile['id'] ?>&tab=posts" class="profile-tab<?= $activeTab === 'posts' ? ' active' : '' ?>">
                    <span class="profile-tab-label">Posts (<?= (int)$feed['total'] ?>)</span>
                </a>
                <?php if ($isOwnProfile): ?>
                <a href="profile.php?id=<?= (int)$profile['id'] ?>&tab=likes" class="profile-tab<?= $activeTab === 'likes' ? ' active' : '' ?>">
                    <span class="profile-tab-label">Likes (<?= (int)$likesTotal ?>)</span>
                </a>
                <?php endif; ?>
            </div>

            <?php if ($activeTab === 'likes'): ?>
                <?php if (empty($likedPosts)): ?>
                    <div class="empty-state">
                        <p class="empty-text"><?= $isOwnProfile ? 'You have not liked any posts yet.' : 'This user has not liked any posts yet.' ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($likedPosts as $likedPost): ?>
                        <?php renderPostCard($likedPost, $currentUser, $bookmarkedIds); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <?php if (empty($feed['posts'])): ?>
                    <div class="empty-state">
                        <p class="empty-text"><?= $isOwnProfile ? 'You have not posted anything yet.' : 'This user has not posted anything yet.' ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($feed['posts'] as $feedPost): ?>
                        <?php renderPostCard($feedPost, $currentUser, $bookmarkedIds); ?>
                    <?php endforeach; ?>

                    <?php if ($feed['page'] > 1 || $feed['has_more']): ?>
                        <div class="pager">
                            <?php if ($feed['page'] > 1): ?>
                                <a href="profile.php?id=<?= (int)$profile['id'] ?>&tab=<?= urlencode($activeTab) ?>&page=<?= (int)$feed['page'] - 1 ?>">&larr; Previous</a>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <span class="pager-info">Page <?= (int)$feed['page'] ?></span>
                            <?php if ($feed['has_more']): ?>
                                <a href="profile.php?id=<?= (int)$profile['id'] ?>&tab=<?= urlencode($activeTab) ?>&page=<?= (int)$feed['page'] + 1 ?>">Next &rarr;</a>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="col-side">
            <?php renderTrendingHashtagsWidget($db); ?>
            <?php if ($currentUser): ?>
                <?php renderWhoToFollowWidget($db, (int)$currentUser['id']); ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Followers modal -->
    <div id="followers-modal" class="modal-overlay" onclick="if (event.target === this) toggleModal('followers-modal')">
        <div class="modal-box">
            <div class="modal-head">
                <h3>Followers</h3>
                <button type="button" class="modal-close" onclick="toggleModal('followers-modal')">&times;</button>
            </div>
            <?php if (empty($followers)): ?>
                <p class="widget-empty">No followers yet.</p>
            <?php else: ?>
                <?php foreach ($followers as $follower): ?>
                    <?php
                        $fInitial = strtoupper(mb_substr(trim($follower['name']), 0, 1, 'UTF-8'));
                        $fIsFollowing = isset($viewerFollowingIds[(int)$follower['id']]);
                        $fIsSelf = $currentUser && (int)$follower['id'] === (int)$currentUser['id'];
                    ?>
                    <div class="user-row">
                        <a href="profile.php?id=<?= (int)$follower['id'] ?>" class="user-row-link">
                            <span class="avatar-sm<?= avatarToneClass((int)$follower['id'], (string)($follower['role'] ?? '')) ?>"><?= htmlspecialchars($fInitial) ?></span>
                            <span class="user-row-text"><strong><?= htmlspecialchars($follower['name']) ?></strong><span><?= htmlspecialchars(Authorization::displayLabel($follower['role'] ?? '')) ?></span></span>
                        </a>
                        <?php if ($currentUser && !$fIsSelf): ?>
                            <button type="button" class="btn-follow<?= $fIsFollowing ? ' following' : '' ?>" data-follow-toggle data-user-id="<?= (int)$follower['id'] ?>"><?= $fIsFollowing ? 'Following' : 'Follow' ?></button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Following modal -->
    <div id="following-modal" class="modal-overlay" onclick="if (event.target === this) toggleModal('following-modal')">
        <div class="modal-box">
            <div class="modal-head">
                <h3>Following</h3>
                <button type="button" class="modal-close" onclick="toggleModal('following-modal')">&times;</button>
            </div>
            <?php if (empty($following)): ?>
                <p class="widget-empty">Not following anyone yet.</p>
            <?php else: ?>
                <?php foreach ($following as $followed): ?>
                    <?php
                        $fInitial = strtoupper(mb_substr(trim($followed['name']), 0, 1, 'UTF-8'));
                        $fIsFollowing = isset($viewerFollowingIds[(int)$followed['id']]);
                        $fIsSelf = $currentUser && (int)$followed['id'] === (int)$currentUser['id'];
                    ?>
                    <div class="user-row">
                        <a href="profile.php?id=<?= (int)$followed['id'] ?>" class="user-row-link">
                            <span class="avatar-sm<?= avatarToneClass((int)$followed['id'], (string)($followed['role'] ?? '')) ?>"><?= htmlspecialchars($fInitial) ?></span>
                            <span class="user-row-text"><strong><?= htmlspecialchars($followed['name']) ?></strong><span><?= htmlspecialchars(Authorization::displayLabel($followed['role'] ?? '')) ?></span></span>
                        </a>
                        <?php if ($currentUser && !$fIsSelf): ?>
                            <button type="button" class="btn-follow<?= $fIsFollowing ? ' following' : '' ?>" data-follow-toggle data-user-id="<?= (int)$followed['id'] ?>"><?= $fIsFollowing ? 'Following' : 'Follow' ?></button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit profile modal (own profile only) -->
    <?php if ($isOwnProfile): ?>
        <div id="edit-profile-modal" class="modal-overlay" onclick="if (event.target === this) toggleModal('edit-profile-modal')">
            <div class="modal-box">
                <div class="modal-head">
                    <h3>Edit Profile</h3>
                    <button type="button" class="modal-close" onclick="toggleModal('edit-profile-modal')">&times;</button>
                </div>
                <form method="POST" action="profile_action.php">
                    <?= Csrf::field() ?>
                    <div class="form-group">
                        <label class="form-label" for="edit-name">Name</label>
                        <input class="form-control" type="text" id="edit-name" name="name" maxlength="255" required value="<?= htmlspecialchars($profile['name']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit-bio">Bio (max 500 characters)</label>
                        <textarea class="form-control" id="edit-bio" name="bio" rows="3" maxlength="500"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <span class="form-label" id="cover-style-label">Cover style</span>
                        <div class="cover-picker" role="radiogroup" aria-labelledby="cover-style-label">
                            <?php foreach (User::COVER_STYLES as $styleOption): ?>
                                <label class="cover-swatch cover-<?= htmlspecialchars($styleOption) ?>">
                                    <input type="radio" name="cover_style" value="<?= htmlspecialchars($styleOption) ?>"<?= $coverStyle === $styleOption ? ' checked' : '' ?>>
                                    <span class="cover-swatch-name"><?= htmlspecialchars(ucfirst($styleOption)) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
                <hr style="border: none; border-top: 1px solid var(--line); margin: 20px 0;">
                <h3 style="font-size: 1rem; margin-bottom: 12px;">Change password</h3>
                <form method="POST" action="profile_action.php" id="change-password-form">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label class="form-label" for="current-password">Current password</label>
                        <div class="password-input-wrap">
                            <input class="form-control" type="password" id="current-password" name="current_password" required autocomplete="current-password">
                            <button type="button" class="btn-toggle-pw" data-toggle-pw aria-label="Toggle password visibility">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new-password">New password</label>
                        <div class="password-input-wrap">
                            <input class="form-control" type="password" id="new-password" name="new_password" required autocomplete="new-password">
                            <button type="button" class="btn-toggle-pw" data-toggle-pw aria-label="Toggle password visibility">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                        <p class="field-hint">Min 8 characters, with 1 uppercase, 1 lowercase, 1 number, and 1 special character.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirm-password">Confirm new password</label>
                        <div class="password-input-wrap">
                            <input class="form-control" type="password" id="confirm-password" name="confirm_password" required autocomplete="new-password">
                            <button type="button" class="btn-toggle-pw" data-toggle-pw aria-label="Toggle password visibility">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Change Password</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
