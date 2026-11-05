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
    $links = [
        'home' => ['index.php', 'Home'],
        'explore' => ['explore.php', 'Explore'],
        'bookmarks' => ['bookmarks.php', 'Bookmarks'],
        'profile' => [$viewerId ? 'profile.php?id=' . $viewerId : 'login.php', 'Profile'],
    ];
    echo '<nav class="col-nav">';
    foreach ($links as $key => [$href, $label]) {
        $isActive = $key === $active ? ' nav-col-active' : '';
        echo '<a href="' . htmlspecialchars($href) . '" class="nav-col-link' . $isActive . '">'
            . htmlspecialchars($label) . '</a>';
    }
    echo '</nav>';
}

/**
 * Who-to-follow widget (WBS 3.3.5) for the right sidebar.
 * Shows up to 5 suggested users with AJAX follow buttons.
 */
function renderWhoToFollowWidget(PDO $db, int $userId): void {
    require_once __DIR__ . '/Follow.php';
    $followModel = new Follow($db);
    $suggestions = $followModel->getSuggestions($userId, 5);

    echo '<div class="widget">';
    echo '<h4 class="widget-title">Who to follow</h4>';
    if (empty($suggestions)) {
        echo '<p class="widget-empty">You are following everyone so far.</p>';
    } else {
        foreach ($suggestions as $user) {
            $initial = strtoupper(mb_substr(trim($user['name']), 0, 1, 'UTF-8'));
            echo '<div class="user-row">';
            echo '<a href="profile.php?id=' . (int)$user['id'] . '" class="user-row-link">';
            echo '<span class="avatar-sm">' . htmlspecialchars($initial) . '</span>';
            echo '<span class="user-row-text"><strong>' . htmlspecialchars($user['name']) . '</strong>'
                . '<span>' . (int)$user['follower_count'] . ' followers</span></span>';
            echo '</a>';
            echo '<button type="button" class="btn-follow" data-follow-toggle data-user-id="'
                . (int)$user['id'] . '">Follow</button>';
            echo '</div>';
        }
    }
    echo '<a href="explore.php" class="widget-more">Show more &rarr;</a>';
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

    echo '<div class="post-card" data-post-card>';
    echo '<div class="post-card-head">';
    echo '<span class="avatar-md">' . htmlspecialchars($authorInitial) . '</span>';
    echo '<div><div class="post-card-author"><a href="profile.php?id=' . (int)$post['user_id'] . '">'
        . htmlspecialchars($post['author_name']) . '</a>'
        . '<span class="role-badge">' . htmlspecialchars($post['author_role']) . '</span></div>';
    echo '<div class="post-card-time">' . htmlspecialchars($timeAgo) . '</div></div>';
    echo '</div>';
    echo '<p class="post-card-content">' . htmlspecialchars($post['content']) . '</p>';
    echo '<div class="post-card-actions">';
    echo '<a href="post.php?id=' . $postId . '">View Post</a>';
    echo '<a href="post.php?id=' . $postId . '">' . $replyCount . ' repl'
        . ($replyCount === 1 ? 'y' : 'ies') . '</a>';
    if ($currentUser) {
        echo '<button type="button" class="btn-bookmark" data-bookmark-toggle data-post-id="'
            . $postId . '"' . ($isBookmarked && $removeCardOnUnsave ? ' data-remove-card="1"' : '') . '>'
            . ($isBookmarked ? 'Saved' : 'Save') . '</button>';
    }
    echo '</div>';
    echo '</div>';
}
