<?php
/**
 * Automated Unit Test Suite for Sprint 4 features:
 * Likes, Hashtags & Discovery, and Redis/Memory Cache Fallback.
 * Agile SPM - CampusConnect (WBS 4.1, 4.2, 4.3)
 *
 * Run via CLI: php tests/Sprint4Test.php
 */
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Post.php';
require_once __DIR__ . '/../classes/Like.php';
require_once __DIR__ . '/../classes/Hashtag.php';
require_once __DIR__ . '/../classes/Cache.php';

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$postModel = new Post($db);
$likeModel = new Like($db);
$hashtagModel = new Hashtag($db);
$cache = Cache::getInstance();

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

echo "=== SPRINT 4 AUTOMATED TEST SUITE ===" . PHP_EOL . PHP_EOL;

// 1. Setup Test Users
$uniq = time() . '_' . rand(1000, 9999);
$password = "StrongPass123!";

$userModel->register("Liker Liam {$uniq}", "liam_{$uniq}@nu.edu.pk", $password);
$userModel->register("Poster Penny {$uniq}", "penny_{$uniq}@nu.edu.pk", $password);
$userModel->register("Fan Farhan {$uniq}", "farhan_{$uniq}@nu.edu.pk", $password);

$userA = $userModel->verifyPassword("liam_{$uniq}@nu.edu.pk", $password);
$userB = $userModel->verifyPassword("penny_{$uniq}@nu.edu.pk", $password);
$userC = $userModel->verifyPassword("farhan_{$uniq}@nu.edu.pk", $password);

$liamId = (int)$userA['id'];
$pennyId = (int)$userB['id'];
$farhanId = (int)$userC['id'];

// 2. Setup Test Posts
$postId1 = $postModel->create($pennyId, "Excited for #CampusConnect launch! #Tech #agile");
$postId2 = $postModel->create($pennyId, "Studying in library. #Tech #Finals");
$postId3 = $postModel->create($pennyId, "This post will be deleted later. #Tech");

assertTest("Posts created with IDs", $postId1 > 0 && $postId2 > 0 && $postId3 > 0);

echo PHP_EOL . "--- LIKE SYSTEM TESTS (WBS 4.1) ---" . PHP_EOL;

// Invalid inputs
assertTest("Like with invalid userId is rejected", $likeModel->add(0, $postId1) === false);
assertTest("Like with invalid postId is rejected", $likeModel->add($liamId, 0) === false);
assertTest("Like non-existent post is rejected", $likeModel->add($liamId, 999999) === false);

// Initial state
assertTest("Initial like count is 0", $likeModel->countByPost($postId1) === 0);
assertTest("User has not liked post yet", $likeModel->isLiked($liamId, $postId1) === false);

// Add like
assertTest("Like succeeds", $likeModel->add($liamId, $postId1) === true);
assertTest("isLiked returns true", $likeModel->isLiked($liamId, $postId1) === true);
assertTest("Like count is 1", $likeModel->countByPost($postId1) === 1);
assertTest("Duplicate like is idempotent", $likeModel->add($liamId, $postId1) === true && $likeModel->countByPost($postId1) === 1);

// Second user likes
assertTest("Second user can like", $likeModel->add($farhanId, $postId1) === true);
assertTest("Like count increments to 2", $likeModel->countByPost($postId1) === 2);

// Toggle like
$toggleOff = $likeModel->toggle($liamId, $postId1);
assertTest("Toggle unlikes post", $toggleOff !== null && $toggleOff['liked'] === false && $toggleOff['count'] === 1);
assertTest("isLiked returns false after toggle", $likeModel->isLiked($liamId, $postId1) === false);

$toggleOn = $likeModel->toggle($liamId, $postId1);
assertTest("Toggle likes post back", $toggleOn !== null && $toggleOn['liked'] === true && $toggleOn['count'] === 2);
assertTest("isLiked returns true after re-toggle", $likeModel->isLiked($liamId, $postId1) === true);

// User liked posts list
$liamLiked = $likeModel->getLikedPosts($liamId);
assertTest("getLikedPosts returns liked post", count($liamLiked) >= 1 && (int)$liamLiked[0]['id'] === $postId1);
assertTest("countByUser counts liked posts", $likeModel->countByUser($liamId) >= 1);

// Users who liked
$likers = $likeModel->getUsersWhoLiked($postId1);
assertTest("getUsersWhoLiked returns 2 users", count($likers) === 2);

// Soft delete behavior
$postModel->delete($postId3, $pennyId);
assertTest("Liking soft-deleted post fails", $likeModel->add($liamId, $postId3) === false);

echo PHP_EOL . "--- HASHTAG TESTS (WBS 4.2) ---" . PHP_EOL;

// Extraction
$tags = Hashtag::extractTags("Loving the #CampusLife and #CS101! #CampusLife #spm_project");
assertTest("extractTags extracts clean lowercase tags", in_array('campuslife', $tags, true) && in_array('cs101', $tags, true));
assertTest("extractTags deduplicates identical tags", count(array_keys($tags, 'campuslife')) === 1);
assertTest("extractTags handles empty text", Hashtag::extractTags("") === []);

// Automatic indexing on create
$trending = $hashtagModel->getTrending(10);
$trendingTags = array_column($trending, 'tag');
assertTest("Post creation indexed #tech tag", in_array('tech', $trendingTags, true));
assertTest("Post creation indexed #campusconnect tag", in_array('campusconnect', $trendingTags, true));

// Tag query
$techPosts = $hashtagModel->getPostsByTag('tech');
assertTest("getPostsByTag returns tagged posts (excluding deleted)", count($techPosts) === 2);

// Update post re-syncs tags
$postModel->update($postId1, $pennyId, "Updated text now with #DesignSystem and #Frontend");
$updatedTechPosts = $hashtagModel->getPostsByTag('tech');
assertTest("Updating post removes unreferenced tags", count($updatedTechPosts) === 1);
$designPosts = $hashtagModel->getPostsByTag('designsystem');
assertTest("Updating post indexes new tags", count($designPosts) === 1);

// HTML rendering
$rendered = Hashtag::renderHashtagLinks(htmlspecialchars("Check out #CampusConnect and #FAST_NUCES"));
assertTest("renderHashtagLinks renders clickable links", strpos($rendered, '<a href="explore.php?tag=campusconnect"') !== false);
assertTest("renderHashtagLinks supports underscores", strpos($rendered, '<a href="explore.php?tag=fast_nuces"') !== false);

echo PHP_EOL . "--- CACHING LAYER TESTS (WBS 4.3) ---" . PHP_EOL;

assertTest("Cache instance is Singleton", $cache instanceof Cache);
assertTest("Cache driver identified", in_array($cache->getDriverName(), ['Redis', 'MemoryFallback'], true));

$testKey = "test_sprint4_" . $uniq;
assertTest("Cache set and get work", $cache->set($testKey, ['alpha' => 123, 'beta' => 'test']) && $cache->get($testKey)['alpha'] === 123);

$rememberKey = "remember_" . $uniq;
$callCount = 0;
$getter = function() use (&$callCount) {
    $callCount++;
    return "computed_value";
};

$val1 = $cache->remember($rememberKey, 300, $getter);
$val2 = $cache->remember($rememberKey, 300, $getter);
assertTest("remember() caches on first run and hits on second run", $val1 === "computed_value" && $val2 === "computed_value" && $callCount === 1);

$cache->delete($testKey);
assertTest("delete() removes cached key", $cache->get($testKey) === null);

$cache->set("feed_user_1", "feed1", 300);
$cache->set("feed_user_2", "feed2", 300);
$cache->set("other_key", "keep", 300);
$cache->deleteByPrefix("feed_");
assertTest("deleteByPrefix() flushes prefixed keys", $cache->get("feed_user_1") === null && $cache->get("other_key") === "keep");

echo PHP_EOL . "=== SPRINT 4 TEST RESULTS: {$passed} / {$total} PASSED ===" . PHP_EOL;
if ($passed === $total) {
    echo ">>> ALL TESTS PASSED SUCCESSFULLY! <<<" . PHP_EOL;
}
