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
$activeTab = ($_GET['tab'] ?? 'posts') === 'likes' ? 'likes' : 'posts';
$likesTotal = 0;
$likedPosts = [];

if ($profile) {
    $followModel = new Follow($db);
    $likeModel = new Like($db);
    $followers = $followModel->getFollowers((int)$profile['id'], 100);
    $following = $followModel->getFollowing((int)$profile['id'], 100);
    $likesTotal = $likeModel->countByUser((int)$profile['id']);

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

            <!-- Profile header card -->
            <?php $profileIsAdmin = Authorization::isOfficialRole($profile['role'] ?? ''); ?>
            <div class="profile-card">
                <div class="profile-cover cover-<?= htmlspecialchars($coverStyle) ?>" aria-hidden="true"></div>
                <div class="profile-head">
                    <span class="avatar-lg<?= avatarToneClass((int)$profile['id'], (string)($profile['role'] ?? '')) ?>"><?= htmlspecialchars($profileInitial) ?></span>
                    <div class="profile-main">
                        <div class="profile-name">
                            <?= htmlspecialchars($profile['name']) ?>
                        </div>
                        <div class="profile-email"><?php if ($isOwnProfile): ?><?= htmlspecialchars($profile['email']) ?> &middot; <?php endif; ?><?= htmlspecialchars(Authorization::displayLabel($profile['role'] ?? '')) ?></div>
                        <?php if (!empty($profile['bio'])): ?>
                            <p class="profile-bio"><?= htmlspecialchars($profile['bio']) ?></p>
                        <?php endif; ?>
                        <div class="profile-stats">
                            <button type="button" class="stat-btn" onclick="toggleModal('followers-modal')">
                                <strong data-followers-count-for="<?= (int)$profile['id'] ?>"><?= $followerCount ?></strong>
                                <span> follower<?= $followerCount === 1 ? '' : 's' ?></span>
                            </button>
                            <button type="button" class="stat-btn" onclick="toggleModal('following-modal')">
                                <strong><?= $followingCount ?></strong>
                                <span> following</span>
                            </button>
                            <span><strong><?= (int)$feed['total'] ?></strong> posts</span>
                        </div>
                    </div>
                    <div class="profile-action">
                        <?php if ($isOwnProfile): ?>
                            <button type="button" class="btn-outline" onclick="toggleModal('edit-profile-modal')">Edit Profile</button>
                        <?php elseif ($currentUser): ?>
                            <button type="button" class="btn-follow<?= $isFollowing ? ' following' : '' ?>" data-follow-toggle data-user-id="<?= (int)$profile['id'] ?>"><?= $isFollowing ? 'Following' : 'Follow' ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Profile Tabs (Posts / Likes) -->
            <div class="profile-tabs">
                <a href="profile.php?id=<?= (int)$profile['id'] ?>&tab=posts" class="profile-tab<?= $activeTab === 'posts' ? ' active' : '' ?>">Posts (<?= (int)$feed['total'] ?>)</a>
                <a href="profile.php?id=<?= (int)$profile['id'] ?>&tab=likes" class="profile-tab<?= $activeTab === 'likes' ? ' active' : '' ?>">Likes (<?= (int)$likesTotal ?>)</a>
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
                                <a href="profile.php?id=<?= (int)$profile['id'] ?>&page=<?= (int)$feed['page'] - 1 ?>">&larr; Previous</a>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <span class="pager-info">Page <?= (int)$feed['page'] ?></span>
                            <?php if ($feed['has_more']): ?>
                                <a href="profile.php?id=<?= (int)$profile['id'] ?>&page=<?= (int)$feed['page'] + 1 ?>">Next &rarr;</a>
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
                        $fIsFollowing = $currentUser ? $followModel->isFollowing((int)$currentUser['id'], (int)$follower['id']) : false;
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
                        $fIsFollowing = $currentUser ? $followModel->isFollowing((int)$currentUser['id'], (int)$followed['id']) : false;
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
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
