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
        <div class="col-head admin-header-flex">
            <span>Admin Panel</span>
            <a href="benchmark.php" class="admin-bench-pill" title="Run Database vs Cache Latency Benchmark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" aria-hidden="true">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                <span>Performance Benchmark</span>
            </a>
        </div>

        <?php if ($flashMessage): ?>
            <div class="alert <?= $flashType === 'success' ? 'alert-success' : 'alert-error' ?>">
                <div><?= htmlspecialchars($flashMessage) ?></div>
            </div>
        <?php endif; ?>

        <h3 class="section-title">Users (<?= (int)$totalUsers ?>)</h3>

        <div class="search-card" style="padding: 12px 16px; border-bottom: 1px solid var(--line);">
            <form method="GET" action="admin.php" class="admin-search-wrap">
                <svg class="search-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="search" name="q" class="admin-search-input" placeholder="Search users by name or email…" value="<?= htmlspecialchars($search) ?>" aria-label="Search users">
                <button type="submit" class="admin-search-btn">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="admin.php" class="btn-clear-search">Clear</a>
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
                <div class="admin-user-card">
                    <div class="admin-user-left">
                        <a class="admin-user-avatar" href="profile.php?id=<?= (int)$row['id'] ?>">
                            <span class="avatar-md<?= avatarToneClass((int)$row['id'], (string)$row['role']) ?>"><?= htmlspecialchars(strtoupper(mb_substr(trim($row['name']), 0, 1, 'UTF-8'))) ?></span>
                        </a>
                        <div class="admin-user-info">
                            <div class="admin-user-name-line">
                                <a class="admin-user-name" href="profile.php?id=<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['name']) ?></a>
                                <?php if (($row['role'] ?? 'student') !== 'student'): ?>
                                    <span class="role-badge role-badge-<?= htmlspecialchars($row['role']) ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                        <?= htmlspecialchars(Authorization::displayLabel($row['role'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="admin-user-meta">
                                <span><?= htmlspecialchars($row['email']) ?></span>
                                <span>&middot;</span>
                                <span><?= (int)$row['post_count'] ?> <?= pluralize((int)$row['post_count'], 'post') ?></span>
                                <span>&middot;</span>
                                <span>Joined <?= htmlspecialchars(date('M j, Y', strtotime($row['created_at']))) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="admin-user-actions">
                        <?php if ($isSelf): ?>
                            <span class="admin-self-pill">This is you</span>
                        <?php else: ?>
                            <form method="POST" action="admin_action.php" class="admin-role-form" onsubmit="return confirm('Change <?= htmlspecialchars(addslashes($row['name'])) ?>’s role? Their sessions will be signed out.');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="assign_role">
                                <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
                                <select name="role" class="admin-role-select" aria-label="Role for <?= htmlspecialchars($row['name']) ?>">
                                    <?php foreach (Authorization::ROLES as $roleOption): ?>
                                        <option value="<?= htmlspecialchars($roleOption) ?>"<?= Authorization::normalizeRole($row['role']) === $roleOption ? ' selected' : '' ?>><?= htmlspecialchars(Authorization::displayLabel($roleOption)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="admin-btn-action">Update</button>
                            </form>
                            <form method="POST" action="admin_action.php" onsubmit="return confirm('Reset <?= htmlspecialchars(addslashes($row['name'])) ?>’s password? A temporary password will be shown once.');" style="margin: 0;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="admin-btn-reset" title="Reset password">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                    Reset
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
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
                            <a class="action-reply" href="post.php?id=<?= (int)$modPost['id'] ?>" style="color: var(--accent); text-decoration: none; font-weight: 600; font-size: 0.88rem;">View post &rarr;</a>
                            <span class="spacer"></span>
                            <form method="POST" action="post_action.php" onsubmit="return confirm('Delete this post? This cannot be undone.');" class="inline-form" style="margin: 0;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="post_id" value="<?= (int)$modPost['id'] ?>">
                                <input type="hidden" name="return" value="index">
                                <button type="submit" class="admin-mod-delete-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    Delete Post
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="col-side">
        <div class="side-card" style="margin-bottom: 16px;">
            <h3 class="widget-title">Performance Lab</h3>
            <p style="font-size: 0.86rem; color: var(--muted); margin-top: 4px; line-height: 1.45;">
                Run real-time latency tests comparing direct database queries against in-memory / Redis cache reads.
            </p>
            <div style="margin-top: 12px;">
                <a href="benchmark.php" class="btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; font-size: 0.85rem; font-weight: 700; padding: 7px 14px; border-radius: 9999px; text-decoration: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" aria-hidden="true">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                    <span>Run Benchmark</span>
                </a>
            </div>
        </div>

        <div class="side-card">
            <h3 class="widget-title">About Roles & Permissions</h3>
            <div style="display: flex; flex-direction: column; gap: 14px; padding: 6px 0;">
                <div>
                    <span class="role-badge role-badge-student">Student</span>
                    <p style="font-size: 0.86rem; color: var(--muted); margin-top: 4px; line-height: 1.4;">Can post, follow, like, reply, and bookmark.</p>
                </div>
                <div>
                    <span class="role-badge role-badge-society_admin">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="12" height="12"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Society Admin
                    </span>
                    <p style="font-size: 0.86rem; color: var(--muted); margin-top: 4px; line-height: 1.4;">All student capabilities plus creating official society announcements.</p>
                </div>
                <div>
                    <span class="role-badge role-badge-system_admin">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="12" height="12"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        System Admin
                    </span>
                    <p style="font-size: 0.86rem; color: var(--muted); margin-top: 4px; line-height: 1.4;">Full campus platform control: role assignments, password resets, and moderation.</p>
                </div>
            </div>
            <p style="font-size: 0.82rem; color: var(--muted); border-top: 1px solid var(--line); padding-top: 10px; margin-top: 10px;">Changing a role signs that user out of all sessions immediately.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
