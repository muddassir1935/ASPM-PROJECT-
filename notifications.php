<?php
/**
 * Notifications Center Page (Sprint 5, WBS 5.x).
 *
 * Displays all in-app notifications for the logged-in user:
 * - New followers
 * - Replies to user's posts
 * - Likes on user's posts
 *
 * Supports single and bulk mark-as-read, direct navigation to referenced
 * posts or profiles, and real-time state updates.
 */
require_once 'config/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Notification.php';
require_once 'includes/social_widgets.php';

require_once 'includes/header.php';

if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$userId = (int)$currentUser['id'];
$notifModel = new Notification($db);

$notifications = $notifModel->listAll($userId, 100, 0);
$unreadCount = $notifModel->countUnread($userId);
?>

<div class="layout-3col">
    <?php renderLeftNav('notifications'); ?>

    <div class="col-main">
        <div class="notif-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
            <h2 class="col-head" style="margin-bottom: 0;">Notifications</h2>
            <?php if ($unreadCount > 0): ?>
                <button type="button" id="btn-mark-all-read" class="btn-outline" style="font-size: 0.82rem; padding: 5px 12px;">Mark all as read</button>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <span class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width: 44px; height: 44px;">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </span>
                <h4 class="empty-title">No notifications yet</h4>
                <p class="empty-text">
                    When people follow you, reply to your posts, or like your chirps, you will see notifications here.
                </p>
            </div>
        <?php else: ?>
            <div class="notif-feed">
                <?php foreach ($notifications as $item): ?>
                    <?php
                        $notifId = (int)$item['id'];
                        $isRead = (bool)$item['is_read'];
                        $actorId = (int)($item['actor_id'] ?? 0);
                        $actorName = $item['actor_name'] ?: 'Someone';
                        $actorInitial = strtoupper(mb_substr(trim($actorName), 0, 1, 'UTF-8'));
                        $type = $item['type'];
                        $refId = (int)($item['reference_id'] ?? 0);

                        // Route target based on notification type
                        if ($type === Notification::TYPE_FOLLOW) {
                            $targetUrl = "profile.php?id=" . ($actorId > 0 ? $actorId : $userId);
                        } else {
                            $targetUrl = "post.php?id=" . $refId;
                        }
                    ?>
                    <div class="notif-card <?= $isRead ? 'is-read' : 'is-unread' ?>" data-notif-id="<?= $notifId ?>">
                        <div class="notif-type-icon">
                            <?php if ($type === Notification::TYPE_LIKE): ?>
                                <span class="notif-badge-icon badge-like">
                                    <svg viewBox="0 0 24 24" fill="#E0245E" stroke="#E0245E" stroke-width="1.8" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                                </span>
                            <?php elseif ($type === Notification::TYPE_REPLY): ?>
                                <span class="notif-badge-icon badge-reply">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z"/></svg>
                                </span>
                            <?php else: ?>
                                <span class="notif-badge-icon badge-follow">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#00BA7C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="notif-content-wrap">
                            <div class="notif-user-row">
                                <?php if ($actorId > 0): ?>
                                    <a href="profile.php?id=<?= $actorId ?>" class="notif-avatar-link">
                                        <span class="avatar-sm<?= avatarToneClass($actorId, (string)($item['actor_role'] ?? '')) ?>"><?= htmlspecialchars($actorInitial) ?></span>
                                    </a>
                                <?php endif; ?>
                                <div class="notif-body-text">
                                    <a href="<?= htmlspecialchars($targetUrl) ?>" class="notif-target-link" onclick="markNotificationRead(<?= $notifId ?>)">
                                        <p class="notif-msg"><?= htmlspecialchars($item['message']) ?></p>
                                    </a>
                                    <span class="notif-time"><?= htmlspecialchars($item['time_ago']) ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if (!$isRead): ?>
                            <button type="button" class="btn-text btn-notif-mark" onclick="markNotificationRead(<?= $notifId ?>, this)" title="Mark as read">
                                &bull;
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-side">
        <?php renderTrendingHashtagsWidget($db); ?>
        <?php renderWhoToFollowWidget($db, $userId); ?>
    </div>
</div>

<script>
    function markNotificationRead(id, btnElement) {
        fetch('notification_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=mark_read&id=' + encodeURIComponent(id)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                var card = document.querySelector('.notif-card[data-notif-id="' + id + '"]');
                if (card) {
                    card.classList.remove('is-unread');
                    card.classList.add('is-read');
                }
                if (btnElement) {
                    btnElement.remove();
                }
                updateUnreadBadges(data.unread_count);
            }
        });
    }

    var markAllBtn = document.getElementById('btn-mark-all-read');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function () {
            markAllBtn.disabled = true;
            fetch('notification_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_all_read'
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    document.querySelectorAll('.notif-card.is-unread').forEach(function (card) {
                        card.classList.remove('is-unread');
                        card.classList.add('is-read');
                    });
                    document.querySelectorAll('.btn-notif-mark').forEach(function (b) {
                        b.remove();
                    });
                    markAllBtn.remove();
                    updateUnreadBadges(0);
                }
            })
            .finally(function () {
                markAllBtn.disabled = false;
            });
        });
    }

    function updateUnreadBadges(count) {
        document.querySelectorAll('[data-notif-badge]').forEach(function (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>
