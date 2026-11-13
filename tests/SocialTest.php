<?php
/**
 * Automated Unit Test Suite for Sprint 3 social features:
 * Follow, Reply, Bookmark, personalized feed, profile edit, search.
 * Agile SPM - CampusConnect
 *
 * Run via CLI: php tests/SocialTest.php
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Post.php';
require_once __DIR__ . '/../classes/Follow.php';
require_once __DIR__ . '/../classes/Reply.php';
require_once __DIR__ . '/../classes/Bookmark.php';

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$postModel = new Post($db);
$followModel = new Follow($db);
$replyModel = new Reply($db);
$bookmarkModel = new Bookmark($db);

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

// Setup test users
$uniq = time() . '_' . rand(1000, 9999);
$password = "StrongPass123!";

$userModel->register("Follower Fran {$uniq}", "follower_{$uniq}@nu.edu.pk", $password);
$userModel->register("Followed Fred {$uniq}", "followed_{$uniq}@nu.edu.pk", $password);
$userModel->register("Stranger Sam {$uniq}", "stranger_{$uniq}@nu.edu.pk", $password);
$userModel->register("Admin Amy {$uniq}", "admin3_{$uniq}@nu.edu.pk", $password);

$userA = $userModel->verifyPassword("follower_{$uniq}@nu.edu.pk", $password);
$userB = $userModel->verifyPassword("followed_{$uniq}@nu.edu.pk", $password);
$userC = $userModel->verifyPassword("stranger_{$uniq}@nu.edu.pk", $password);
$userAdmin = $userModel->verifyPassword("admin3_{$uniq}@nu.edu.pk", $password);
$db->exec("UPDATE users SET role = 'system_admin' WHERE id = " . (int)$userAdmin['id']);

$aliceId = (int)$userA['id'];
$bobId = (int)$userB['id'];
$samId = (int)$userC['id'];
$adminId = (int)$userAdmin['id'];

echo "--- FOLLOW TESTS ---" . PHP_EOL;

assertTest("Self-follow is rejected", $followModel->follow($aliceId, $aliceId) === false);
assertTest("Follow with invalid id is rejected", $followModel->follow($aliceId, 0) === false);
assertTest("Follow succeeds", $followModel->follow($aliceId, $bobId) === true);
assertTest("isFollowing true after follow", $followModel->isFollowing($aliceId, $bobId) === true);
assertTest("Follow is one-way (reverse is false)", $followModel->isFollowing($bobId, $aliceId) === false);
assertTest("Duplicate follow stays idempotent", $followModel->follow($aliceId, $bobId) === true
    && $followModel->countFollowers($bobId) === 1);
assertTest("Follower count is 1", $followModel->countFollowers($bobId) === 1);
assertTest("Following count is 1", $followModel->countFollowing($aliceId) === 1);
assertTest("Toggle unfollows", $followModel->toggle($aliceId, $bobId) === false
    && $followModel->isFollowing($aliceId, $bobId) === false);
assertTest("Toggle follows again", $followModel->toggle($aliceId, $bobId) === true
    && $followModel->isFollowing($aliceId, $bobId) === true);
assertTest("Toggle self returns null", $followModel->toggle($aliceId, $aliceId) === null);
assertTest("Unfollow removes the row", $followModel->unfollow($aliceId, $bobId) === true
    && $followModel->isFollowing($aliceId, $bobId) === false);
assertTest("Unfollow of a non-follow still succeeds", $followModel->unfollow($aliceId, $bobId) === true);

echo "--- WHO-TO-FOLLOW SUGGESTIONS ---" . PHP_EOL;

$followModel->follow($aliceId, $bobId);
$suggestions = $followModel->getSuggestions($aliceId, 10);
$suggestionIds = array_column($suggestions, 'id');
assertTest("Suggestions exclude self", !in_array($aliceId, $suggestionIds, true));
assertTest("Suggestions exclude already-followed users", !in_array($bobId, $suggestionIds, true));
assertTest("Suggestions include strangers", in_array($samId, $suggestionIds, true));

echo "--- PERSONALIZED FEED TESTS ---" . PHP_EOL;

$bobPost = $postModel->create($bobId, "Bob's followed post {$uniq}");
$samPost = $postModel->create($samId, "Sam's stranger post {$uniq}");
$alicePost = $postModel->create($aliceId, "Alice's own post {$uniq}");

$feed = $postModel->getPersonalizedFeed($aliceId, 50, 0);
$feedIds = array_column($feed, 'id');
assertTest("Personalized feed includes own post", in_array($alicePost, $feedIds, true));
assertTest("Personalized feed includes followed user's post", in_array($bobPost, $feedIds, true));
assertTest("Personalized feed excludes strangers", !in_array($samPost, $feedIds, true));
assertTest("Personalized count matches feed size",
    $postModel->countPersonalized($aliceId) === count($feed));

echo "--- REPLY TESTS ---" . PHP_EOL;

assertTest("Reply validation rejects empty", $replyModel->validateContent("   ")['valid'] === false);
assertTest("Reply validation rejects over 280 chars",
    $replyModel->validateContent(str_repeat("x", 281))['valid'] === false);
$replyId = $replyModel->create($aliceId, $bobPost, "Nice post, Bob!");
assertTest("Reply creation succeeds", is_int($replyId) && $replyId > 0);
assertTest("Reply to unknown post fails", $replyModel->create($aliceId, 999999999, "Hello?") === false);
$replyId2 = $replyModel->create($samId, $bobPost, "Second reply here");
$replies = $replyModel->getByPostId($bobPost);
assertTest("Replies listed oldest first",
    count($replies) === 2 && (int)$replies[0]['id'] === (int)$replyId && (int)$replies[1]['id'] === (int)$replyId2);
assertTest("Reply count is 2", $replyModel->countByPost($bobPost) === 2);
assertTest("Non-author cannot delete a reply",
    $replyModel->delete($replyId, $samId, false) === false);
assertTest("Author can delete own reply",
    $replyModel->delete($replyId, $aliceId, false) === true
    && $replyModel->countByPost($bobPost) === 1);
assertTest("Admin can delete another user's reply",
    $replyModel->delete($replyId2, $adminId, true) === true
    && $replyModel->countByPost($bobPost) === 0);

echo "--- BOOKMARK TESTS ---" . PHP_EOL;

assertTest("Bookmark succeeds", $bookmarkModel->add($aliceId, $bobPost) === true);
assertTest("isBookmarked true after save", $bookmarkModel->isBookmarked($aliceId, $bobPost) === true);
assertTest("Duplicate bookmark stays idempotent", $bookmarkModel->add($aliceId, $bobPost) === true
    && $bookmarkModel->countByUser($aliceId) === 1);
assertTest("Toggle removes bookmark", $bookmarkModel->toggle($aliceId, $bobPost) === false
    && $bookmarkModel->isBookmarked($aliceId, $bobPost) === false);
assertTest("Toggle saves again", $bookmarkModel->toggle($aliceId, $bobPost) === true);
$saved = $bookmarkModel->getBookmarkedPosts($aliceId);
assertTest("Bookmarked list contains the post",
    count($saved) === 1 && (int)$saved[0]['id'] === (int)$bobPost);
assertTest("Bookmark of unknown post fails", $bookmarkModel->add($aliceId, 999999999) === false);
$postModel->delete($bobPost, $bobId, false); // soft-delete
assertTest("Soft-deleted post drops out of bookmark list",
    $bookmarkModel->countByUser($aliceId) === 0 && $bookmarkModel->getBookmarkedPosts($aliceId) === []);
assertTest("Reply to soft-deleted post fails",
    $replyModel->create($aliceId, $bobPost, "Too late") === false);

echo "--- PROFILE EDIT & SEARCH TESTS ---" . PHP_EOL;

assertTest("Profile edit succeeds",
    $userModel->updateProfile($aliceId, "Follower Fran Updated {$uniq}", "CS junior, loves OpenMP.") === true);
$profile = $userModel->getById($aliceId);
assertTest("Profile edit persists name and bio",
    $profile && $profile['name'] === "Follower Fran Updated {$uniq}" && $profile['bio'] === "CS junior, loves OpenMP.");
assertTest("Profile edit rejects empty name",
    $userModel->updateProfile($aliceId, "   ", "bio") === false);
assertTest("Profile edit rejects over-long bio",
    $userModel->updateProfile($aliceId, "Fran", str_repeat("b", 501)) === false);
assertTest("getById returns false for unknown user", $userModel->getById(999999999) === false);
$found = $userModel->searchUsers("Followed Fred {$uniq}", $aliceId);
assertTest("User search finds by name",
    count($found) === 1 && (int)$found[0]['id'] === $bobId);
assertTest("User search excludes the searcher",
    $userModel->searchUsers("Follower Fran Updated {$uniq}", $aliceId) === []);
assertTest("Empty user search returns nothing", $userModel->searchUsers("   ", $aliceId) === []);
// Sprint 5 (H5): email must neither match nor leak through user search.
assertTest("User search does not match by email",
    $userModel->searchUsers("followed_{$uniq}@nu.edu.pk", $aliceId) === []);
assertTest("User search results carry no email",
    !isset($found[0]['email']));

$foundPosts = $postModel->searchPosts("stranger post {$uniq}");
assertTest("Post search finds by content",
    count($foundPosts) === 1 && (int)$foundPosts[0]['id'] === (int)$samPost);
assertTest("Post search count matches",
    $postModel->countSearch("stranger post {$uniq}") === 1);
assertTest("Empty post search returns nothing", $postModel->searchPosts("   ") === []);

echo PHP_EOL . "Result: {$passed}/{$total} passed" . PHP_EOL;
exit($passed === $total ? 0 : 1);
