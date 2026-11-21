<?php
/**
 * Profile Replies tab (intent 002, unit 006-profile-tab-pagination).
 *
 * Reply::getRepliesByUser() / countByUser(): only the profile user's
 * replies, newest first, 20 per page, skipping soft-deleted replies and
 * replies on soft-deleted posts. profile.php: the tab is public, uses
 * clampPage with 20 per page, and the pager keeps &tab=replies.
 *
 * Run via CLI: php tests/ProfileRepliesTest.php
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Post.php';
require_once __DIR__ . '/../classes/Reply.php';

$passed = 0;
$failed = 0;

function assertProfileReplies(string $name, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] {$name}\n";
        $passed++;
    } else {
        echo "[FAIL] {$name}" . ($detail !== '' ? " ({$detail})" : '') . "\n";
        $failed++;
    }
}

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$postModel = new Post($db);
$replyModel = new Reply($db);

$uniq = time() . '_' . rand(1000, 9999);
$userModel->register("Replier Rita {$uniq}", "rita_{$uniq}@nu.edu.pk", 'StrongPass123!');
$userModel->register("Other Omar {$uniq}", "omar_{$uniq}@nu.edu.pk", 'StrongPass123!');
$ritaId = (int)$userModel->verifyPassword("rita_{$uniq}@nu.edu.pk", 'StrongPass123!')['id'];
$omarId = (int)$userModel->verifyPassword("omar_{$uniq}@nu.edu.pk", 'StrongPass123!')['id'];

$postA = (int)$postModel->create($omarId, "Post A {$uniq}");
$postB = (int)$postModel->create($omarId, "Post B {$uniq}");

// 23 replies by Rita on post A, with increasing timestamps so order is known.
$ritaReplyIds = [];
for ($i = 1; $i <= 23; $i++) {
    $id = (int)$replyModel->create($ritaId, $postA, "Rita reply {$i}");
    $db->exec("UPDATE replies SET created_at = '2026-11-01 10:" . sprintf('%02d', $i) . ":00' WHERE id = {$id}");
    $ritaReplyIds[$i] = $id;
}
// Someone else's reply must never show on Rita's tab.
$replyModel->create($omarId, $postA, "Omar reply");
// A reply Rita deleted, and a reply on a post that was later deleted.
$deletedReply = (int)$replyModel->create($ritaId, $postA, "Deleted reply");
$replyModel->delete($deletedReply, $ritaId);
$replyModel->create($ritaId, $postB, "Reply on deleted post");
$postModel->delete($postB, $omarId);

$page1 = $replyModel->getRepliesByUser($ritaId, 20, 0);
$page2 = $replyModel->getRepliesByUser($ritaId, 20, 20);

assertProfileReplies('countByUser counts only visible replies by the user', $replyModel->countByUser($ritaId) === 23,
    (string)$replyModel->countByUser($ritaId));
assertProfileReplies('Page 1 holds 20 replies', count($page1) === 20, (string)count($page1));
assertProfileReplies('Page 2 holds the remaining 3', count($page2) === 3, (string)count($page2));
assertProfileReplies('Newest reply comes first', (int)$page1[0]['id'] === $ritaReplyIds[23]);
assertProfileReplies('Oldest reply is last on page 2', (int)end($page2)['id'] === $ritaReplyIds[1]);
$all = array_merge($page1, $page2);
assertProfileReplies('Only the profile user\'s replies are returned',
    array_unique(array_map(fn($r) => (int)$r['user_id'], $all)) === [$ritaId]);
$contents = array_column($all, 'content');
assertProfileReplies('Soft-deleted replies are excluded', !in_array('Deleted reply', $contents, true));
assertProfileReplies('Replies on soft-deleted posts are excluded', !in_array('Reply on deleted post', $contents, true));
assertProfileReplies('Each row links to its parent post and has a time',
    (int)$page1[0]['post_id'] === $postA && ($page1[0]['time_ago'] ?? '') !== '');
assertProfileReplies('Invalid user id gives an empty list and zero count',
    $replyModel->getRepliesByUser(0) === [] && $replyModel->countByUser(-1) === 0);
assertProfileReplies('User with no replies gets an empty list', $replyModel->getRepliesByUser($omarId + 1000) === []);

// profile.php wiring (mirrors the Likes tab checks in ProfilePaginationTest).
$source = str_replace("\r\n", "\n", file_get_contents(__DIR__ . '/../profile.php'));
assertProfileReplies('Replies tab is selectable on any profile (not owner-only)',
    str_contains($source, "} elseif (\$requestedTab === 'replies') {\n    \$activeTab = 'replies';"));
assertProfileReplies('Replies tab uses 20-item pages for count, query, and pager',
    str_contains($source, '$repliesPerPage = 20;')
        && str_contains($source, "clampPage((int)(\$_GET['page'] ?? 1), \$repliesTotal, \$repliesPerPage)")
        && str_contains($source, "getRepliesByUser((int)\$profile['id'], \$repliesPerPage, (\$repliesPage - 1) * \$repliesPerPage)")
        && str_contains($source, '$repliesPage * $repliesPerPage < $repliesTotal'));
assertProfileReplies('Replies pager preserves its active tab',
    str_contains($source, "'profile.php?id=' . (int)\$profile['id'] . '&tab=replies'"));
assertProfileReplies('Replies tab link sits outside the owner-only Likes block',
    (function (string $src): bool {
        $tabs = strpos($src, '<div class="profile-tabs-header">');
        $replies = strpos($src, '&tab=replies" class="profile-tab', (int)$tabs);
        $ownerOnly = strpos($src, '<?php if ($isOwnProfile): ?>', (int)$tabs);
        return $tabs !== false && $replies !== false && $ownerOnly !== false && $replies < $ownerOnly;
    })($source));
assertProfileReplies('Empty state and escaped output',
    str_contains($source, 'No replies yet.') && str_contains($source, "htmlspecialchars(\$userReply['content'])"));

echo "\nProfileRepliesTest: {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
