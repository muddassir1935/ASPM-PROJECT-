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

if ($profile) {
    $followModel = new Follow($db);
    $followers = $followModel->getFollowers((int)$profile['id'], 100);
    $following = $followModel->getFollowing((int)$profile['id'], 100);
    if ($currentUser && !$isOwnProfile) {
        $isFollowing = $followModel->isFollowing((int)$currentUser['id'], (int)$profile['id']);
    }
    $feedPage = max(1, (int)($_GET['page'] ?? 1));
    $feedController = new PostController(new Post($db), $currentUser);
    $feed = $feedController->getUserFeed((int)$profile['id'], $feedPage, 10);
    if ($currentUser) {
        $bookmarkModel = new Bookmark($db);
        $bookmarkedIds = array_column($bookmarkModel->getBookmarkedPosts((int)$currentUser['id'], 500), 'id');
        $bookmarkedIds = array_map('intval', $bookmarkedIds);
    }
}
?>

<?php if (!$profile): ?>
    <div style="max-width: 850px; margin: 1rem auto;">
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 3rem; text-align: center; box-shadow: var(--shadow-sm);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
            <h4 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 8px;">User not found</h4>
            <p style="font-size: 0.9rem; color: #64748b;">This profile may not exist, or the link is incorrect.</p>
        </div>
    </div>
<?php else: ?>
    <?php
        $profileInitial = strtoupper(mb_substr(trim($profile['name']), 0, 1, 'UTF-8'));
        $followerCount = (int)$profile['follower_count'];
        $followingCount = (int)$profile['following_count'];
    ?>
    <div class="layout-3col">
        <?php renderLeftNav('profile'); ?>

        <div>
            <?php if ($flashMessage): ?>
                <div style="padding: 12px 14px; border-radius: 10px; font-size: 0.9rem; font-weight: 500; margin-bottom: 1rem; <?= $flashType === 'success' ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;' ?>">
                    <?= htmlspecialchars($flashMessage) ?>
                </div>
            <?php endif; ?>

            <!-- Profile header card -->
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: flex-start; gap: 14px; flex-wrap: wrap;">
                    <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem; flex-shrink: 0;">
                        <?= htmlspecialchars($profileInitial) ?>
                    </div>
                    <div style="flex: 1; min-width: 220px;">
                        <div style="font-weight: 800; color: #0f172a; font-size: 1.3rem;">
                            <?= htmlspecialchars($profile['name']) ?>
                            <span class="role-badge"><?= htmlspecialchars($profile['role']) ?></span>
                        </div>
                        <div style="font-size: 0.85rem; color: #64748b;"><?= htmlspecialchars($profile['email']) ?></div>
                        <?php if (!empty($profile['bio'])): ?>
                            <p style="font-size: 0.95rem; color: #1e293b; line-height: 1.6; margin-top: 8px; white-space: pre-wrap; word-wrap: break-word;"><?= htmlspecialchars($profile['bio']) ?></p>
                        <?php endif; ?>
                        <div style="display: flex; gap: 16px; margin-top: 12px; font-size: 0.88rem;">
                            <button type="button" onclick="toggleModal('followers-modal')" style="background: none; border: none; cursor: pointer; padding: 0; font-size: 0.88rem;">
                                <strong data-followers-count-for="<?= (int)$profile['id'] ?>"><?= $followerCount ?></strong>
                                <span style="color: #64748b;"> follower<?= $followerCount === 1 ? '' : 's' ?></span>
                            </button>
                            <button type="button" onclick="toggleModal('following-modal')" style="background: none; border: none; cursor: pointer; padding: 0; font-size: 0.88rem;">
                                <strong><?= $followingCount ?></strong>
                                <span style="color: #64748b;"> following</span>
                            </button>
                            <span style="color: #64748b;"><strong style="color: #0f172a;"><?= (int)$feed['total'] ?></strong> posts</span>
                        </div>
                    </div>
                    <div>
                        <?php if ($isOwnProfile): ?>
                            <button type="button" onclick="toggleModal('edit-profile-modal')" style="background: white; color: var(--primary); border: 1.5px solid var(--primary); padding: 7px 16px; border-radius: 20px; font-weight: 700; font-size: 0.85rem; cursor: pointer;">Edit Profile</button>
                        <?php elseif ($currentUser): ?>
                            <button type="button" class="btn-follow<?= $isFollowing ? ' following' : '' ?>" data-follow-toggle data-user-id="<?= (int)$profile['id'] ?>" style="padding: 8px 18px; font-size: 0.88rem;"><?= $isFollowing ? 'Following' : 'Follow' ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- User's posts -->
            <h3 style="font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-bottom: 1rem;">Posts</h3>
            <?php if (empty($feed['posts'])): ?>
                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2.5rem; text-align: center; box-shadow: var(--shadow-sm);">
                    <p style="font-size: 0.9rem; color: #64748b;"><?= $isOwnProfile ? 'You have not posted anything yet.' : 'This user has not posted anything yet.' ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($feed['posts'] as $feedPost): ?>
                    <?php renderPostCard($feedPost, $currentUser, $bookmarkedIds); ?>
                <?php endforeach; ?>

                <?php if ($feed['page'] > 1 || $feed['has_more']): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0;">
                        <?php if ($feed['page'] > 1): ?>
                            <a href="profile.php?id=<?= (int)$profile['id'] ?>&page=<?= (int)$feed['page'] - 1 ?>" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.9rem;">&larr; Previous</a>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>
                        <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">Page <?= (int)$feed['page'] ?></span>
                        <?php if ($feed['has_more']): ?>
                            <a href="profile.php?id=<?= (int)$profile['id'] ?>&page=<?= (int)$feed['page'] + 1 ?>" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.9rem;">Next &rarr;</a>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="col-side">
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
                            <span class="avatar-sm"><?= htmlspecialchars($fInitial) ?></span>
                            <span class="user-row-text"><strong><?= htmlspecialchars($follower['name']) ?></strong><span><?= htmlspecialchars($follower['role']) ?></span></span>
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
                            <span class="avatar-sm"><?= htmlspecialchars($fInitial) ?></span>
                            <span class="user-row-text"><strong><?= htmlspecialchars($followed['name']) ?></strong><span><?= htmlspecialchars($followed['role']) ?></span></span>
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
                    <div class="form-group">
                        <label class="form-label" for="edit-name">Name</label>
                        <input class="form-control" type="text" id="edit-name" name="name" maxlength="255" required value="<?= htmlspecialchars($profile['name']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit-bio">Bio (max 500 characters)</label>
                        <textarea class="form-control" id="edit-bio" name="bio" rows="3" maxlength="500"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
