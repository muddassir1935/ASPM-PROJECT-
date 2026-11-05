<?php
/**
 * Shared Sprint 3 UI partials (WBS 3.3).
 *
 * These helpers render the 3-column layout pieces and post cards used by
 * the logged-in pages (index, profile, explore, bookmarks). They echo
 * HTML directly, matching the template style of the existing pages.
 * All user-controlled output is escaped with htmlspecialchars here.
 */

/**
 * Left navigation column. $active is one of: home, explore, bookmarks, profile.
 */
function renderLeftNav(string $active): void {
    $viewerId = isset($GLOBALS['currentUser']['id']) ? (int)$GLOBALS['currentUser']['id'] : 0;
    $svgAttrs = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    $links = [
        'home' => ['index.php', 'Home', '<svg ' . $svgAttrs . '><path d="M4 11.5 12 4l8 7.5"/><path d="M6.5 9.5V20h11V9.5"/><path d="M10 20v-5h4v5"/></svg>'],
        'explore' => ['explore.php', 'Explore', '<svg ' . $svgAttrs . '><circle cx="12" cy="12" r="8.5"/><path d="M15.5 8.5 13.5 13.5 8.5 15.5 10.5 10.5Z"/></svg>'],
        'bookmarks' => ['bookmarks.php', 'Bookmarks', '<svg ' . $svgAttrs . '><path d="M7 3.5h10V20l-5-3.8L7 20Z"/></svg>'],
        'profile' => [$viewerId ? 'profile.php?id=' . $viewerId : 'login.php', 'Profile', '<svg ' . $svgAttrs . '><circle cx="12" cy="8" r="3.6"/><path d="M5 20c.8-3.4 3.6-5 7-5s6.2 1.6 7 5"/></svg>'],
    ];
    echo '<nav class="col-nav" aria-label="Section">';
    foreach ($links as $key => [$href, $label, $icon]) {
        $isActive = $key === $active ? ' nav-col-active' : '';
        echo '<a href="' . htmlspecialchars($href) . '" class="nav-col-link' . $isActive . '">'
            . $icon . '<span>' . htmlspecialchars($label) . '</span></a>';
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
                . '<span>' . (int)$user['follower_count'] . ' followers</span></span>';
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
 * One post card, shared by profile, explore and bookmarks pages.
 * $post is a row from Post/Bookmark selects (author_*, time_ago, reply_count).
 * $bookmarkedIds lists post ids the viewer has saved. When
 * $removeCardOnUnsave is true (bookmarks page), unsaving removes the card.
 */
function renderPostCard(array $post, ?array $currentUser, array $bookmarkedIds = [], bool $removeCardOnUnsave = false): void {
    $postId = (int)$post['id'];
    $authorInitial = strtoupper(mb_substr(trim($post['author_name']), 0, 1, 'UTF-8'));
    $timeAgo = $post['time_ago'] ?? $post['created_at'];
    $replyCount = (int)($post['reply_count'] ?? 0);
    $isBookmarked = in_array($postId, $bookmarkedIds, true);

    $isAdmin = ($post['author_role'] ?? '') === 'admin';
    $replySvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z"/></svg>';
    $megaSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10.5v3h2.5L18 19V5L6.5 10.5H4Z"/><path d="M8 14v4.5"/></svg>';
    echo '<div class="post-card" data-post-card>';
    echo '<span class="avatar-md' . avatarToneClass((int)$post['user_id'], (string)($post['author_role'] ?? '')) . '">' . htmlspecialchars($authorInitial) . '</span>';
    echo '<div class="post-body">';
    if ($isAdmin) {
        echo '<div class="announce-line">' . $megaSvg . 'Announcement</div>';
    }
    echo '<div class="post-card-head">';
    echo '<a class="post-card-author" href="profile.php?id=' . (int)$post['user_id'] . '">'
        . htmlspecialchars($post['author_name']) . '</a>';
    echo '<span class="post-card-time">&middot; ' . htmlspecialchars($timeAgo) . '</span>';
    echo '</div>';
    echo '<p class="post-card-content">' . htmlspecialchars($post['content']) . '</p>';
    echo '<div class="post-card-actions">';
    echo '<a class="action-reply" href="post.php?id=' . $postId . '" aria-label="View post and replies"><span class="action-ic">'
        . $replySvg . '</span>' . $replyCount . '</a>';
    echo '<span class="spacer"></span>';
    if ($currentUser) {
        echo '<button type="button" class="btn-bookmark" data-bookmark-toggle data-post-id="'
            . $postId . '"' . ($isBookmarked && $removeCardOnUnsave ? ' data-remove-card="1"' : '') . '>'
            . ($isBookmarked ? 'Saved' : 'Save') . '</button>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
