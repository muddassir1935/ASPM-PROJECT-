<?php
/**
 * Automated Unit Test Suite for Sprint 5 Notifications Module (WBS 5.x).
 * Agile SPM - CampusConnect
 *
 * Run via CLI: php tests/NotificationTest.php
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Post.php';
require_once __DIR__ . '/../classes/Follow.php';
require_once __DIR__ . '/../classes/Reply.php';
require_once __DIR__ . '/../classes/Like.php';
require_once __DIR__ . '/../classes/Notification.php';

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$postModel = new Post($db);
$followModel = new Follow($db);
$replyModel = new Reply($db);
$likeModel = new Like($db);
$notifModel = new Notification($db);

$passed = 0;
$total = 0;

function assertTest(string $testName, bool $condition, ?string $detail = null) {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo " [PASS] Test {$total}: {$testName}" . PHP_EOL;
    } else {
        echo " [FAIL] Test {$total}: {$testName}" . ($detail ? " ({$detail})" : "") . PHP_EOL;
    }
}

echo "=== SPRINT 5 NOTIFICATIONS AUTOMATED TEST SUITE ===" . PHP_EOL . PHP_EOL;

// 1. Setup Test Users
$uniq = time() . '_' . rand(1000, 9999);
$password = "StrongPass123!";

$userModel->register("Target Tara {$uniq}", "tara_{$uniq}@nu.edu.pk", $password);
$userModel->register("Actor Adam {$uniq}", "adam_{$uniq}@nu.edu.pk", $password);
$userModel->register("Intruder Ian {$uniq}", "ian_{$uniq}@nu.edu.pk", $password);

$userA = $userModel->verifyPassword("tara_{$uniq}@nu.edu.pk", $password);
$userB = $userModel->verifyPassword("adam_{$uniq}@nu.edu.pk", $password);
$userC = $userModel->verifyPassword("ian_{$uniq}@nu.edu.pk", $password);

$taraId = (int)$userA['id'];
$adamId = (int)$userB['id'];
$ianId  = (int)$userC['id'];

// 2. Unit Model Validation Tests
echo "--- NOTIFICATION MODEL VALIDATION ---" . PHP_EOL;

assertTest("Create rejects invalid userId", $notifModel->create(0, 'like', 1, 'Someone liked your post.', $adamId) === false);
assertTest("Create rejects empty type", $notifModel->create($taraId, '', 1, 'Someone liked your post.', $adamId) === false);
assertTest("Create rejects empty message", $notifModel->create($taraId, 'like', 1, '', $adamId) === false);

// 3. Self-action suppression
echo PHP_EOL . "--- SELF-ACTION SUPPRESSION ---" . PHP_EOL;
assertTest("Notification for self-action is suppressed", $notifModel->create($taraId, 'like', 1, 'You liked your post.', $taraId) === false);

// 4. Creation & Retrieval
echo PHP_EOL . "--- CREATION, LISTING & UNREAD COUNTS ---" . PHP_EOL;
$initialCount = $notifModel->countUnread($taraId);

$setupPostId = (int)$postModel->create($taraId, "Setup post for notification tests");
$notifId1 = $notifModel->create($taraId, Notification::TYPE_LIKE, $setupPostId, 'Adam liked your post.', $adamId);
assertTest("Valid notification creates successfully with ID", is_int($notifId1) && $notifId1 > 0);

$countAfterOne = $notifModel->countUnread($taraId);
assertTest("countUnread increments by 1", $countAfterOne === $initialCount + 1);

$notifId2 = $notifModel->create($taraId, Notification::TYPE_REPLY, $setupPostId, 'Adam replied to your post.', $adamId);
// Actor is Ian (not Adam): the live follow trigger test below has Adam
// follow Tara, and an identical follow notification would be deduplicated.
$notifId3 = $notifModel->create($taraId, Notification::TYPE_FOLLOW, $ianId, 'Ian started following you.', $ianId);
assertTest("Multiple notifications create successfully", is_int($notifId2) && is_int($notifId3));
assertTest("countUnread reflects 3 new notifications", $notifModel->countUnread($taraId) === $initialCount + 3);

$unreadList = $notifModel->listUnread($taraId);
assertTest("listUnread returns unread items", count($unreadList) >= 3);
assertTest("listUnread includes actor details and relative time", 
    !empty($unreadList[0]['actor_name']) && isset($unreadList[0]['time_ago']));

// 5. Mark as Read & Single Mark
echo PHP_EOL . "--- MARK READ & MARK ALL READ ---" . PHP_EOL;
assertTest("Intruder markRead returns false for another user's notification", $notifModel->markRead($notifId1, $ianId) === false);
assertTest("Unread count unchanged after intruder markRead attempt", $notifModel->countUnread($taraId) === $initialCount + 3);

$markedOne = $notifModel->markRead($notifId1, $taraId);
assertTest("Recipient can mark single notification as read", $markedOne === true);
assertTest("countUnread decrements after single markRead", $notifModel->countUnread($taraId) === $initialCount + 2);

$markedAll = $notifModel->markAllRead($taraId);
assertTest("markAllRead returns true", $markedAll === true);
assertTest("countUnread is 0 after markAllRead", $notifModel->countUnread($taraId) === 0);

$allList = $notifModel->listAll($taraId);
assertTest("listAll returns historical read notifications", count($allList) >= 3);

// 6. Delete Notification
echo PHP_EOL . "--- DELETE NOTIFICATION ---" . PHP_EOL;
$notifId4 = $notifModel->create($taraId, 'test', 99, 'Test to delete', $adamId);
assertTest("Created test notification to delete", is_int($notifId4));
assertTest("Recipient can delete notification", $notifModel->delete($notifId4, $taraId) === true);

$notifId5 = $notifModel->create($taraId, 'test', 100, 'Test intruder delete', $adamId);
assertTest("Intruder delete returns false for another user's notification", $notifModel->delete($notifId5, $ianId) === false);
assertTest("Recipient can delete notification after intruder attempt", $notifModel->delete($notifId5, $taraId) === true);

// 7. Live Social Trigger Integration Tests
echo PHP_EOL . "--- SOCIAL TRIGGER INTEGRATION TESTS ---" . PHP_EOL;

// 7a. Follow Trigger
$beforeFollowCount = $notifModel->countUnread($taraId);
$followModel->follow($adamId, $taraId);
$afterFollowCount = $notifModel->countUnread($taraId);
assertTest("Following user triggers unread notification for recipient", $afterFollowCount === $beforeFollowCount + 1);

$latestNotif = $notifModel->listUnread($taraId)[0] ?? [];
assertTest("Follow notification has type 'follow'", ($latestNotif['type'] ?? '') === Notification::TYPE_FOLLOW);
assertTest("Follow notification actor references follower ID", (int)($latestNotif['actor_id'] ?? 0) === $adamId);
assertTest("Follow notification reference_id is null (safe for posts FK)", ($latestNotif['reference_id'] ?? null) === null);

// 7b. Reply Trigger
$taraPostId = $postModel->create($taraId, "Hello world post by Tara! #CampusConnect");
$beforeReplyCount = $notifModel->countUnread($taraId);
$replyModel->create($adamId, $taraPostId, "Great post Tara!");
$afterReplyCount = $notifModel->countUnread($taraId);
assertTest("Replying to post triggers notification for post author", $afterReplyCount === $beforeReplyCount + 1);

// Replying to own post must not trigger notification
$replyModel->create($taraId, $taraPostId, "Replying to my own post.");
assertTest("Replying to own post does not create self-notification", $notifModel->countUnread($taraId) === $afterReplyCount);

// 7c. Like Trigger
$beforeLikeCount = $notifModel->countUnread($taraId);
$likeModel->add($adamId, $taraPostId);
$afterLikeCount = $notifModel->countUnread($taraId);
assertTest("Liking post triggers notification for post author", $afterLikeCount === $beforeLikeCount + 1);

// Liking own post must not trigger notification
$adamPostId = $postModel->create($adamId, "Adam post text here.");
$beforeSelfLikeCount = $notifModel->countUnread($adamId);
$likeModel->add($adamId, $adamPostId);
assertTest("Liking own post does not create self-notification", $notifModel->countUnread($adamId) === $beforeSelfLikeCount);

// 7d. Audit C10: toggles do not spam the recipient
echo PHP_EOL . "--- DEDUPLICATION (audit C10) ---" . PHP_EOL;
$beforeToggle = count($notifModel->listAll($taraId, 500));
$likeModel->remove($adamId, $taraPostId);
$likeModel->add($adamId, $taraPostId);
$likeModel->remove($adamId, $taraPostId);
$likeModel->add($adamId, $taraPostId);
assertTest("Like/unlike/like does not create duplicate notifications",
    count($notifModel->listAll($taraId, 500)) === $beforeToggle);
$followModel->unfollow($adamId, $taraId);
$followModel->follow($adamId, $taraId);
assertTest("Unfollow/follow does not create a duplicate notification",
    count($notifModel->listAll($taraId, 500)) === $beforeToggle);
$replyModel->create($adamId, $taraPostId, "Second reply");
$replyModel->create($adamId, $taraPostId, "Third reply");
assertTest("Every reply still notifies (replies are never deduplicated)",
    count($notifModel->listAll($taraId, 500)) === $beforeToggle + 2);

// 7e. Audit C20: notifications about a deleted post disappear
echo PHP_EOL . "--- DELETED-POST NOTIFICATIONS (audit C20) ---" . PHP_EOL;
$c20Post = $postModel->create($taraId, "C20 post that will be deleted");
$likeModel->add($ianId, (int)$c20Post);
$c20Before = $notifModel->countUnread($taraId);
$c20Listed = count(array_filter($notifModel->listAll($taraId, 500), fn($n) => (int)$n['reference_id'] === (int)$c20Post && $n['type'] === 'like'));
assertTest("Like notification for a live post is listed", $c20Listed === 1);
$postModel->delete((int)$c20Post, $taraId);
assertTest("Unread count drops when the post is deleted", $notifModel->countUnread($taraId) === $c20Before - 1);
$c20After = count(array_filter($notifModel->listAll($taraId, 500), fn($n) => (int)$n['reference_id'] === (int)$c20Post && $n['type'] === 'like'));
assertTest("Notification for a deleted post is no longer listed", $c20After === 0);
assertTest("Follow notifications are unaffected by post operations",
    count(array_filter($notifModel->listAll($taraId, 500), fn($n) => $n['type'] === 'follow')) >= 1);

// 8. Intent 003 Unit 009 (EF-6): Foreign key on notifications.reference_id
echo PHP_EOL . "--- NOTIFICATION FOREIGN KEY CONSTRAINTS (UNIT 009 / EF-6) ---" . PHP_EOL;

// 8a. Inserting post-type notification with invalid post ID violates DB FK constraint
$invalidFkCaught = false;
try {
    // Direct raw insert bypasses application layer to test DB-level constraint
    $db->prepare("INSERT INTO notifications (user_id, actor_id, type, reference_id, message) VALUES (:u, :a, 'like', 999999, 'Test invalid post FK')")
        ->execute([':u' => $taraId, ':a' => $adamId]);
} catch (PDOException $e) {
    $invalidFkCaught = true;
}
assertTest("DB rejects like notification pointing to non-existent post (FK violation)", $invalidFkCaught === true);

// 8b. Hard-deleting a post cascade-deletes associated notifications in the DB
$cascadePostId = (int)$postModel->create($taraId, "Post to test hard cascade deletion");
$notifModel->create($taraId, Notification::TYPE_LIKE, $cascadePostId, 'Like on cascade test post', $adamId);
$notifModel->create($taraId, Notification::TYPE_REPLY, $cascadePostId, 'Reply on cascade test post', $adamId);
$checkNotifsBefore = $db->query("SELECT COUNT(*) FROM notifications WHERE reference_id = {$cascadePostId}")->fetchColumn();
assertTest("Pre-cascade: notifications exist for post", (int)$checkNotifsBefore === 2);

$db->exec("DELETE FROM posts WHERE id = {$cascadePostId}");
$checkNotifsAfter = $db->query("SELECT COUNT(*) FROM notifications WHERE reference_id = {$cascadePostId}")->fetchColumn();
assertTest("Hard-deleting a post cascades and removes post-type notifications via DB FK", (int)$checkNotifsAfter === 0);

// 8c. Follow notifications create without a post and work with empty posts table
$freshUniq = time() . '_fk_' . rand(100, 999);
$userModel->register("User X {$freshUniq}", "x_{$freshUniq}@nu.edu.pk", "StrongPass123!");
$userModel->register("User Y {$freshUniq}", "y_{$freshUniq}@nu.edu.pk", "StrongPass123!");
$uX = $userModel->verifyPassword("x_{$freshUniq}@nu.edu.pk", "StrongPass123!");
$uY = $userModel->verifyPassword("y_{$freshUniq}@nu.edu.pk", "StrongPass123!");
$uxId = (int)$uX['id'];
$uyId = (int)$uY['id'];

$followOk = $followModel->follow($uxId, $uyId);
assertTest("Follow succeeds between users without requiring any posts", $followOk === true);
$unreadY = $notifModel->listUnread($uyId);
assertTest("Recipient receives follow notification with actor_id set and null reference_id",
    count($unreadY) === 1
        && $unreadY[0]['type'] === Notification::TYPE_FOLLOW
        && (int)$unreadY[0]['actor_id'] === $uxId
        && $unreadY[0]['reference_id'] === null
);

echo PHP_EOL . "=== SPRINT 5 NOTIFICATIONS TEST RESULTS: {$passed} / {$total} PASSED ===" . PHP_EOL;
if ($passed === $total) {
    echo ">>> ALL NOTIFICATION TESTS PASSED SUCCESSFULLY! <<<" . PHP_EOL;
}
