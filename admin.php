<?php
/**
 * Admin Panel (Sprint 5 RBAC).
 *
 * System admins only (permission 'user.manage'). Two sections:
 *  1. Users — searchable, paginated list with a role-assignment form
 *     per user. Changing a role revokes that user's sessions
 *     (admin_action.php), so a demotion takes effect immediately.
 *  2. Moderation — the most recent posts, with the same delete control
 *     any system admin has anywhere (permission 'post.delete_any',
 *     enforced again server-side in PostController).
 *
 * Role changes for your own account are blocked (a system admin must
 * never be able to lock themselves out by accident), as is demoting
 * the last remaining system admin.
 */
require_once 'config/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Authorization.php';
require_once 'classes/User.php';
require_once 'classes/Post.php';
require_once 'classes/Csrf.php';

if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit;
}
if (!Auth::can('user.manage')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_message'] = 'You do not have permission to view the admin panel.';
    header('Location: index.php');
    exit;
}

require_once 'includes/header.php';
require_once 'includes/social_widgets.php';

$flashType = $_SESSION['flash_type'] ?? null;
$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_type'], $_SESSION['flash_message']);

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$currentUserId = (int)$currentUser['id'];

$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$users = $userModel->listUsers($search, $perPage, $offset);
$totalUsers = $userModel->countUsers($search);
$totalPages = max(1, (int)ceil($totalUsers / $perPage));

// Moderation queue: newest posts first (same data as the feed).
$postModel = new Post($db);
$recentPosts = $postModel->getFeed(20, 0, $currentUserId, true);
?>

<div class="layout-3col">
    <?php renderLeftNav('admin'); ?>

    <div class="col-main">
        <h2 class="col-head">Admin Panel</h2>

        <?php if ($flashMessage): ?>
            <div class="alert <?= $flashType === 'success' ? 'alert-success' : 'alert-error' ?>">
                <div><?= htmlspecialchars($flashMessage) ?></div>
            </div>
        <?php endif; ?>

        <h3 class="section-title">Users (<?= (int)$totalUsers ?>)</h3>

        <div class="search-card">
            <form method="GET" action="admin.php" class="search-row" style="flex: 1;">
                <input type="search" name="q" class="form-control" placeholder="Search by name or email…" value="<?= htmlspecialchars($search) ?>" aria-label="Search users">
                <button type="submit" class="btn-primary">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="admin.php" class="btn-text">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($users)): ?>
            <div class="empty-state">
                <p class="empty-title">No users found</p>
                <p class="empty-text">Try a different search.</p>
            </div>
        <?php else: ?>
            <?php foreach ($users as $row): ?>
                <?php $isSelf = (int)$row['id'] === $currentUserId; ?>
                <div class="user-row" style="align-items: flex-start;">
                    <a class="user-row-link" href="profile.php?id=<?= (int)$row['id'] ?>">
                        <span class="avatar-sm<?= avatarToneClass((int)$row['id'], (string)$row['role']) ?>"><?= htmlspecialchars(strtoupper(mb_substr(trim($row['name']), 0, 1, 'UTF-8'))) ?></span>
                        <span class="user-row-text">
                            <strong><?= htmlspecialchars($row['name']) ?></strong>
                            <span><?= htmlspecialchars($row['email']) ?> &middot; <?= htmlspecialchars(Authorization::displayLabel($row['role'])) ?> &middot; <?= (int)$row['post_count'] ?> post<?= (int)$row['post_count'] === 1 ? '' : 's' ?> &middot; joined <?= htmlspecialchars(date('M j, Y', strtotime($row['created_at']))) ?></span>
                        </span>
                    </a>
                    <?php if ($isSelf): ?>
                        <span class="char-counter" title="You cannot change your own role from here.">This is you</span>
                    <?php else: ?>
                        <form method="POST" action="admin_action.php" class="inline-form" onsubmit="return confirm('Change <?= htmlspecialchars(addslashes($row['name'])) ?>’s role? Their sessions will be signed out.');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="assign_role">
                            <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
                            <select name="role" class="form-control" style="width: auto; display: inline-block; padding: 6px 10px; font-size: 0.87rem;" aria-label="Role for <?= htmlspecialchars($row['name']) ?>">
                                <?php foreach (Authorization::ROLES as $roleOption): ?>
                                    <option value="<?= htmlspecialchars($roleOption) ?>"<?= Authorization::normalizeRole($row['role']) === $roleOption ? ' selected' : '' ?>><?= htmlspecialchars(Authorization::displayLabel($roleOption)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-outline">Update role</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($totalPages > 1): ?>
                <div class="pager">
                    <?php if ($page > 1): ?>
                        <a href="admin.php?page=<?= $page - 1 ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">&larr; Newer</a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                    <span class="pager-info">Page <?= $page ?> of <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="admin.php?page=<?= $page + 1 ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">Older &rarr;</a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <h3 class="section-title">Moderation — recent posts</h3>
        <?php if (empty($recentPosts)): ?>
            <p class="result-note">No posts yet.</p>
        <?php else: ?>
            <?php foreach ($recentPosts as $modPost): ?>
                <div class="post-card">
                    <span class="avatar-md<?= avatarToneClass((int)$modPost['user_id'], (string)($modPost['author_role'] ?? '')) ?>"><?= htmlspecialchars(strtoupper(mb_substr(trim($modPost['author_name']), 0, 1, 'UTF-8'))) ?></span>
                    <div class="post-body">
                        <?php if (!empty($modPost['is_announcement'])): ?>
                            <div class="announce-line"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10.5v3h2.5L18 19V5L6.5 10.5H4Z"/><path d="M8 14v4.5"/></svg>Announcement</div>
                        <?php endif; ?>
                        <div class="post-card-head">
                            <a class="post-card-author" href="profile.php?id=<?= (int)$modPost['user_id'] ?>"><?= htmlspecialchars($modPost['author_name']) ?></a>
                            <span class="post-card-time">&middot; <?= htmlspecialchars($modPost['time_ago']) ?></span>
                        </div>
                        <p class="post-card-content"><?= htmlspecialchars($modPost['content']) ?></p>
                        <div class="post-card-actions">
                            <a class="action-reply" href="post.php?id=<?= (int)$modPost['id'] ?>">View post</a>
                            <span class="spacer"></span>
                            <form method="POST" action="post_action.php" onsubmit="return confirm('Delete this post? This cannot be undone.');" class="inline-form">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="post_id" value="<?= (int)$modPost['id'] ?>">
                                <input type="hidden" name="return" value="index">
                                <button type="submit" class="btn-text btn-text-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="col-side">
        <div class="widget">
            <h3 class="widget-title">About roles</h3>
            <p class="widget-empty">
                <strong>Student</strong> — post, follow, like, reply, bookmark.<br>
                <strong>Society Admin</strong> — everything a student can do, plus society announcements.<br>
                <strong>System Admin</strong> — everything, plus user/role management and moderation.
            </p>
            <p class="widget-empty">Changing a role signs that user out of all their sessions, so a demotion takes effect immediately.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
