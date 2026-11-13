<?php
/**
 * Automated Unit Test Suite for Sprint 5 Notifications Module (WBS 5.x).
 * Agile SPM - CampusConnect
 *
 * Run via CLI: php tests/NotificationTest.php
 */
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

$notifId1 = $notifModel->create($taraId, Notification::TYPE_LIKE, 101, 'Adam liked your post.', $adamId);
assertTest("Valid notification creates successfully with ID", is_int($notifId1) && $notifId1 > 0);

$countAfterOne = $notifModel->countUnread($taraId);
assertTest("countUnread increments by 1", $countAfterOne === $initialCount + 1);

$notifId2 = $notifModel->create($taraId, Notification::TYPE_REPLY, 101, 'Adam replied to your post.', $adamId);
$notifId3 = $notifModel->create($taraId, Notification::TYPE_FOLLOW, $adamId, 'Adam started following you.', $adamId);
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
assertTest("Follow notification references follower ID", (int)($latestNotif['reference_id'] ?? 0) === $adamId);

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

echo PHP_EOL . "=== SPRINT 5 NOTIFICATIONS TEST RESULTS: {$passed} / {$total} PASSED ===" . PHP_EOL;
if ($passed === $total) {
    echo ">>> ALL NOTIFICATION TESTS PASSED SUCCESSFULLY! <<<" . PHP_EOL;
}
