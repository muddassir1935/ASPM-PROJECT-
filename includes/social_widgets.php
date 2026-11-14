<?php
/**
 * Shared Sprint 3 & Sprint 4 UI partials (WBS 3.3, 4.1, 4.2).
 *
 * These helpers render the 3-column layout pieces, post cards, who-to-follow,
 * and trending hashtags used by the logged-in pages (index, profile, explore, bookmarks).
 * All user-controlled output is escaped with htmlspecialchars here.
 */
require_once __DIR__ . '/../classes/Hashtag.php';

/**
 * Left navigation column. $active is one of: home, explore, bookmarks, profile.
 */
function renderLeftNav(string $active): void {
    $viewerId = isset($GLOBALS['currentUser']['id']) ? (int)$GLOBALS['currentUser']['id'] : 0;
    $unreadCount = 0;
    if ($viewerId > 0) {
        try {
            require_once __DIR__ . '/../classes/Notification.php';
            $navDb = isset($GLOBALS['db']) ? $GLOBALS['db'] : Database::getInstance()->getConnection();
            $unreadCount = (new Notification($navDb))->countUnread($viewerId);
        } catch (Throwable $e) {}
    }

    $svgAttrs = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    $links = [
        'home' => ['index.php', 'Home', '<svg ' . $svgAttrs . '><path d="M4 11.5 12 4l8 7.5"/><path d="M6.5 9.5V20h11V9.5"/><path d="M10 20v-5h4v5"/></svg>', 0],
        'explore' => ['explore.php', 'Explore', '<svg ' . $svgAttrs . '><circle cx="12" cy="12" r="8.5"/><path d="M15.5 8.5 13.5 13.5 8.5 15.5 10.5 10.5Z"/></svg>', 0],
        'notifications' => ['notifications.php', 'Notifications', '<svg ' . $svgAttrs . '><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>', $unreadCount],
        'bookmarks' => ['bookmarks.php', 'Bookmarks', '<svg ' . $svgAttrs . '><path d="M7 3.5h10V20l-5-3.8L7 20Z"/></svg>', 0],
        'profile' => [$viewerId ? 'profile.php?id=' . $viewerId : 'login.php', 'Profile', '<svg ' . $svgAttrs . '><circle cx="12" cy="8" r="3.6"/><path d="M5 20c.8-3.4 3.6-5 7-5s6.2 1.6 7 5"/></svg>', 0],
    ];
    // Sprint 5 RBAC: system admins get the Admin Panel in the left nav.
    if (class_exists('Auth') && Auth::can('user.manage')) {
        $links['admin'] = ['admin.php', 'Admin', '<svg ' . $svgAttrs . '><path d="M12 3 5 6v5c0 4.5 3 8.2 7 10 4-1.8 7-5.5 7-10V6Z"/><path d="M9 12l2 2 4-4.5"/></svg>', 0];
    }
    echo '<nav class="col-nav" aria-label="Section">';
    foreach ($links as $key => [$href, $label, $icon, $badge]) {
        $isActive = $key === $active ? ' nav-col-active' : '';
        echo '<a href="' . htmlspecialchars($href) . '" class="nav-col-link' . $isActive . '"'
            . ' aria-label="' . htmlspecialchars($label) . '"'
            . ($key === $active ? ' aria-current="page"' : '') . '>'
            . $icon . '<span>' . htmlspecialchars($label) . '</span>';
        if ($key === 'notifications') {
            echo '<span class="nav-badge" data-notif-badge style="' . ($badge > 0 ? '' : 'display:none;') . '">' . ($badge > 99 ? '99+' : (int)$badge) . '</span>';
        }
        echo '</a>';
    }

    // Logged-in User Identity Pill pinned at bottom
    if ($viewerId > 0 && isset($GLOBALS['currentUser']) && is_array($GLOBALS['currentUser'])) {
        $u = $GLOBALS['currentUser'];
        $uInitial = strtoupper(mb_substr(trim($u['name'] ?? ''), 0, 1, 'UTF-8'));
        $uRole = class_exists('Authorization') ? Authorization::displayLabel($u['role'] ?? '') : ($u['role'] ?? 'Student');
        $emailParts = explode('@', $u['email'] ?? '');
        $uHandle = '@' . ($emailParts[0] ?? 'user');

        $isAdmin = class_exists('Auth') && Auth::can('user.manage');

        echo '<div class="nav-user-dock" id="nav-user-dock">';
        echo '<button type="button" class="nav-user-pill" id="nav-user-pill-btn" aria-haspopup="true" aria-expanded="false" title="Account options for ' . htmlspecialchars($u['name'] ?? '') . '">';
        echo '<span class="avatar-sm' . avatarToneClass($viewerId, (string)($u['role'] ?? '')) . '">' . htmlspecialchars($uInitial) . '</span>';
        echo '<div class="nav-user-details">';
        echo '<strong class="nav-user-name">' . htmlspecialchars($u['name'] ?? '') . '</strong>';
        echo '<span class="nav-user-handle">' . htmlspecialchars($uHandle) . ' &middot; ' . htmlspecialchars($uRole) . '</span>';
        echo '</div>';
        echo '<svg class="nav-user-dots" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>';
        echo '</button>';

        echo '<div class="nav-user-dropdown" id="nav-user-dropdown" style="display:none;" role="menu" aria-label="Account options">';
        echo '<div class="nav-user-dropdown-header">';
        echo '<span class="avatar-md' . avatarToneClass($viewerId, (string)($u['role'] ?? '')) . '">' . htmlspecialchars($uInitial) . '</span>';
        echo '<div class="nav-user-details">';
        echo '<strong class="nav-user-name">' . htmlspecialchars($u['name'] ?? '') . '</strong>';
        echo '<span class="nav-user-handle">' . htmlspecialchars($uHandle) . ' &middot; ' . htmlspecialchars($uRole) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '<a href="profile.php?id=' . $viewerId . '" class="nav-user-dropdown-item" role="menuitem">';
        echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>';
        echo '<span>View Profile</span>';
        echo '</a>';

        if ($isAdmin) {
            echo '<a href="admin.php" class="nav-user-dropdown-item" role="menuitem">';
            echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>';
            echo '<span>Admin Panel</span>';
            echo '</a>';
        }

        echo '<a href="bookmarks.php" class="nav-user-dropdown-item" role="menuitem">';
        echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>';
        echo '<span>Bookmarks</span>';
        echo '</a>';

        echo '<div class="nav-user-dropdown-divider"></div>';

        echo '<form method="POST" action="logout.php" style="margin:0; width:100%;">';
        if (class_exists('Csrf')) {
            echo Csrf::field();
        }
        echo '<button type="submit" class="nav-user-dropdown-item item-danger" role="menuitem">';
        echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>';
        echo '<span>Log out ' . htmlspecialchars($uHandle) . '</span>';
        echo '</button>';
        echo '</form>';

        echo '</div>';
        echo '</div>';
    }

    echo '</nav>';
}

/**
 * Who-to-follow widget (WBS 3.3.5) for the right sidebar.
 * Shows up to 3 suggested users with AJAX follow buttons.
 */
function renderWhoToFollowWidget(PDO $db, int $userId): void {
    require_once __DIR__ . '/../classes/Follow.php';
    $followModel = new Follow($db);
    $suggestions = array_slice($followModel->getSuggestions($userId, 5), 0, 3);

    echo '<div class="widget">';
    echo '<h4 class="widget-title">Who to follow</h4>';
    if (empty($suggestions)) {
        echo '<p class="widget-empty">You are following everyone so far.</p>';
    } else {
        foreach ($suggestions as $user) {
            $initial = strtoupper(mb_substr(trim($user['name']), 0, 1, 'UTF-8'));
            echo '<div class="user-row">';
            echo '<a href="profile.php?id=' . (int)$user['id'] . '" class="user-row-link">';
            echo '<span class="avatar-sm' . avatarToneClass((int)$user['id'], (string)($user['role'] ?? '')) . '">' . htmlspecialchars($initial) . '</span>';
            echo '<span class="user-row-text"><strong>' . htmlspecialchars($user['name']) . '</strong>'
                . '<span>' . (int)$user['follower_count'] . ' ' . pluralize((int)$user['follower_count'], 'follower') . '</span></span>';
            echo '</a>';
            echo '<button type="button" class="btn-follow" data-follow-toggle data-user-id="'
                . (int)$user['id'] . '">Follow</button>';
            echo '</div>';
        }
    }
    echo '<a href="explore.php" class="widget-more">Show more</a>';
    echo '</div>';
}

/**
 * Trending Hashtags widget (Sprint 4, WBS 4.2) for the right sidebar.
 * Shows top trending tags by post count.
 */
function renderTrendingHashtagsWidget(PDO $db, int $limit = 5): void {
    require_once __DIR__ . '/../classes/Hashtag.php';
    $hashtagModel = new Hashtag($db);
    $trending = $hashtagModel->getTrending($limit);

    echo '<div class="widget">';
    echo '<h4 class="widget-title">Trends for you</h4>';
    if (empty($trending)) {
        echo '<p class="widget-empty">No trending topics yet.</p>';
    } else {
        foreach ($trending as $item) {
            echo '<div class="trending-row">';
            echo '<a href="explore.php?tag=' . urlencode($item['tag']) . '" class="trending-link">';
            echo '<span class="trending-sub">Trending on Campus</span>';
            echo '<span class="trending-tag">#' . htmlspecialchars($item['tag']) . '</span>';
            echo '<span class="trending-count">' . (int)$item['post_count'] . ' ' . pluralize((int)$item['post_count'], 'post') . '</span>';
            echo '</a>';
            echo '</div>';
        }
    }
    echo '<a href="explore.php" class="widget-more">Show more</a>';
    echo '</div>';
}

/**
 * One post card, shared by profile, explore and bookmarks pages.
 * $post is a row from Post/Bookmark selects (author_*, time_ago, reply_count, like_count, is_liked).
 * $bookmarkedIds lists post ids the viewer has saved. When
 * $removeCardOnUnsave is true (bookmarks page), unsaving removes the card.
 */
function renderPostCard(array $post, ?array $currentUser, array $bookmarkedIds = [], bool $removeCardOnUnsave = false): void {
    $postId = (int)$post['id'];
    $authorInitial = strtoupper(mb_substr(trim($post['author_name']), 0, 1, 'UTF-8'));
    $timeAgo = $post['time_ago'] ?? $post['created_at'];
    $replyCount = (int)($post['reply_count'] ?? 0);
    $likeCount = (int)($post['like_count'] ?? 0);
    $isLiked = !empty($post['is_liked']);
    $isBookmarked = in_array($postId, $bookmarkedIds, true) || !empty($post['is_bookmarked']);

    // Sprint 5 RBAC: the badge marks a post flagged as an announcement
    // (posts.is_announcement), not just any post by an official account.
    $isAnnouncement = !empty($post['is_announcement']);
    $replySvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z"/></svg>';
    $likeSvg = '<svg viewBox="0 0 24 24" fill="' . ($isLiked ? '#e0245e' : 'none') . '" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>';
    $megaSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10.5v3h2.5L18 19V5L6.5 10.5H4Z"/><path d="M8 14v4.5"/></svg>';

    $renderedBody = Hashtag::renderHashtagLinks(htmlspecialchars($post['content']));

    echo '<div class="post-card" data-post-card>';
    echo '<span class="avatar-md' . avatarToneClass((int)$post['user_id'], (string)($post['author_role'] ?? '')) . '">' . htmlspecialchars($authorInitial) . '</span>';
    echo '<div class="post-body">';
    if ($isAnnouncement) {
        echo '<div class="announce-line">' . $megaSvg . 'Announcement</div>';
    }
    $isOwner = $currentUser && (int)$post['user_id'] === (int)$currentUser['id'];
    $canDeleteAny = $currentUser && class_exists('Auth') && Auth::can('post.delete_any');

    echo '<div class="post-card-head">';
    echo '<a class="post-card-author" href="profile.php?id=' . (int)$post['user_id'] . '">'
        . htmlspecialchars($post['author_name']) . '</a>';
    $authorEmail = $post['author_email'] ?? '';
    if ($authorEmail) {
        $handle = explode('@', $authorEmail)[0];
        echo '<span class="post-card-handle">@' . htmlspecialchars($handle) . '</span>';
    }
    echo '<span class="post-card-time">&middot; ' . htmlspecialchars($timeAgo) . '</span>';

    if ($isOwner || $canDeleteAny) {
        echo '<div class="post-menu-wrap">';
        echo '<button type="button" class="post-menu-btn" data-post-menu-toggle="' . $postId . '" aria-label="More options">'
            . '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>'
            . '</button>';
        echo '<div id="post-menu-' . $postId . '" class="post-dropdown-menu" style="display:none;">';
        if ($isOwner) {
            echo '<button type="button" class="post-dropdown-item" onclick="togglePostEdit(' . $postId . '); closeAllPostMenus();">'
                . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>'
                . 'Edit Post</button>';
        }
        if ($isOwner || $canDeleteAny) {
            echo '<form method="POST" action="post_action.php" onsubmit="return confirm(\'Delete this post? This cannot be undone.\');" style="margin:0;">'
                . Csrf::field()
                . '<input type="hidden" name="action" value="delete">'
                . '<input type="hidden" name="post_id" value="' . $postId . '">'
                . '<button type="submit" class="post-dropdown-item item-danger">'
                . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>'
                . 'Delete Post</button>'
                . '</form>';
        }
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    echo '<p class="post-card-content">' . $renderedBody . '</p>';
    echo '<div class="post-card-actions">';
    echo '<a class="action-reply" href="post.php?id=' . $postId . '" aria-label="View post and replies"><span class="action-ic">'
        . $replySvg . '</span>' . $replyCount . '</a>';

    if ($currentUser) {
        echo '<button type="button" class="action-like' . ($isLiked ? ' is-liked' : '') . '" data-like-toggle data-post-id="'
            . $postId . '" aria-label="Like post"><span class="action-ic">' . $likeSvg . '</span><span class="like-count">' . $likeCount . '</span></button>';
    }

    echo '<span class="spacer"></span>';
    if ($currentUser) {
        echo '<button type="button" class="btn-bookmark" data-bookmark-toggle data-post-id="'
            . $postId . '"' . ($isBookmarked && $removeCardOnUnsave ? ' data-remove-card="1"' : '') . '>'
            . ($isBookmarked ? 'Saved' : 'Save') . '</button>';
    }
    echo '</div>';
    if ($isOwner) {
        echo '<form id="post-edit-' . $postId . '" method="POST" action="post_action.php" class="edit-form" style="display: none;">'
            . Csrf::field()
            . '<input type="hidden" name="action" value="update">'
            . '<input type="hidden" name="post_id" value="' . $postId . '">'
            . '<textarea name="content" class="form-control" rows="3" maxlength="280" required>' . htmlspecialchars($post['content']) . '</textarea>'
            . '<div class="edit-form-actions">'
            . '<button type="submit" class="btn-primary">Save Changes</button>'
            . '<button type="button" class="btn-cancel" onclick="togglePostEdit(' . $postId . ')">Cancel</button>'
            . '</div>'
            . '</form>';
    }
    echo '</div>';
    echo '</div>';
}
