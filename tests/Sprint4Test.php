<?php
/**
 * Automated Unit Test Suite for Sprint 4 features:
 * Likes, Hashtags & Discovery, and Redis/Memory Cache Fallback.
 * Agile SPM - CampusConnect (WBS 4.1, 4.2, 4.3)
 *
 * Run via CLI: php tests/Sprint4Test.php
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Post.php';
require_once __DIR__ . '/../classes/Like.php';
require_once __DIR__ . '/../classes/Hashtag.php';
require_once __DIR__ . '/../classes/Cache.php';
require_once __DIR__ . '/../classes/Bookmark.php';

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

// 2. Setup Test Posts with run-isolated tags
$tagA = "tech_" . $uniq;
$tagB = "agile_" . $uniq;
$tagC = "finals_" . $uniq;

$postId1 = $postModel->create($pennyId, "Excited for #CampusConnect launch! #{$tagA} #{$tagB}");
$postId2 = $postModel->create($pennyId, "Studying in library. #{$tagA} #{$tagC}");
$postId3 = $postModel->create($pennyId, "This post will be deleted later. #{$tagA}");

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
$trending = $hashtagModel->getTrending(50);
$trendingTags = array_column($trending, 'tag');
assertTest("Post creation indexed #{$tagA} tag", in_array($tagA, $trendingTags, true));
assertTest("Post creation indexed #campusconnect tag", in_array('campusconnect', $trendingTags, true));

// Tag query
$techPosts = $hashtagModel->getPostsByTag($tagA);
assertTest("getPostsByTag returns tagged posts (excluding deleted)", count($techPosts) === 2);

// Update post re-syncs tags
$newTag = "design_" . $uniq;
$postModel->update($postId1, $pennyId, "Updated text now with #{$newTag} and #Frontend");
$updatedTechPosts = $hashtagModel->getPostsByTag($tagA);
assertTest("Updating post removes unreferenced tags", count($updatedTechPosts) === 1);
$designPosts = $hashtagModel->getPostsByTag($newTag);
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

echo PHP_EOL . "--- SPRINT 4 VERIFICATION FIXES (9 Nov 2026) ---" . PHP_EOL;

// Apostrophes must survive hashtag rendering: htmlspecialchars turns ' into
// the entity &#039; — the renderer must not linkify the "#039" inside it.
$aposRendered = Hashtag::renderHashtagLinks(htmlspecialchars("It's #{$tagA} time, don't miss it"));
assertTest("renderHashtagLinks keeps apostrophe entity intact", strpos($aposRendered, '&#039;') !== false && strpos($aposRendered, 'tag=039') === false);
assertTest("renderHashtagLinks still linkifies real tags next to apostrophes", strpos($aposRendered, 'explore.php?tag=' . $tagA) !== false);

// TinyRedisClient fails silently against an unreachable server (no phpredis
// and no server must never break the app).
$deadClient = new TinyRedisClient('127.0.0.1', 6398, 0.2);
assertTest("TinyRedisClient dead server: ping false", $deadClient->ping() === false);
assertTest("TinyRedisClient dead server: get null, set false", $deadClient->get('nope') === null && $deadClient->set('nope', 'x') === false);

// Cached like counter (WBS 4.3): warms on read, increments on add,
// invalidates on remove — and always agrees with the database.
assertTest("Counter warms at 0 for unliked post", $likeModel->countByPost($postId2) === 0);
$likeModel->add($liamId, $postId2);
assertTest("Counter increments on like", $likeModel->countByPost($postId2) === 1);
$likeModel->remove($liamId, $postId2);
assertTest("Counter invalidates on unlike", $likeModel->countByPost($postId2) === 0);

// Global feed is cached, and post creation invalidates it.
$feedBefore = $postModel->getFeed(5, 0, 0);
$freshPostId = $postModel->create($pennyId, "Fresh cache probe #{$tagA}");
$feedAfter = $postModel->getFeed(5, 0, 0);
assertTest("Global feed cache invalidated on create", (int)$feedAfter[0]['id'] === $freshPostId);

// getLikedPosts: is_liked describes the VIEWER, not the list owner.
$ownerView = $likeModel->getLikedPosts($liamId, 50, 0);
$ownerRow = null;
foreach ($ownerView as $row) { if ((int)$row['id'] === $postId1) { $ownerRow = $row; break; } }
assertTest("Liked posts show is_liked=1 for the owner", $ownerRow !== null && (int)$ownerRow['is_liked'] === 1);
$viewerView = $likeModel->getLikedPosts($liamId, 50, 0, $pennyId);
$viewerRow = null;
foreach ($viewerView as $row) { if ((int)$row['id'] === $postId1) { $viewerRow = $row; break; } }
assertTest("Liked posts show is_liked=0 for a viewer who has not liked", $viewerRow !== null && (int)$viewerRow['is_liked'] === 0);

// Sprint 5 UI polish (M-4): liked + tagged rows carry a relative
// time_ago, never a raw DB timestamp (the Likes tab and hashtag filter
// rendered "2026-11-12 14:16:50" before this fix).
$timeAgoPattern = '/^(just now|\d+[smhd] ago|[A-Z][a-z]{2} \d{1,2}, \d{4})$/';
$rawTsPattern = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';
assertTest("getLikedPosts rows carry relative time_ago",
    $ownerRow !== null && isset($ownerRow['time_ago'])
    && preg_match($rawTsPattern, $ownerRow['time_ago']) !== 1
    && preg_match($timeAgoPattern, $ownerRow['time_ago']) === 1);
$taggedRows = $hashtagModel->getPostsByTag($tagA);
assertTest("getPostsByTag rows carry relative time_ago",
    count($taggedRows) >= 1 && isset($taggedRows[0]['time_ago'])
    && preg_match($rawTsPattern, $taggedRows[0]['time_ago']) !== 1
    && preg_match($timeAgoPattern, $taggedRows[0]['time_ago']) === 1);
assertTest("getLikedPosts rows carry is_announcement flag",
    $ownerRow !== null && array_key_exists('is_announcement', $ownerRow));
assertTest("getPostsByTag rows carry is_announcement flag",
    count($taggedRows) >= 1 && array_key_exists('is_announcement', $taggedRows[0]));

// Bookmarked posts carry real like data for the bookmarks page.
$bookmarkModel = new Bookmark($db);
$bookmarkModel->add($liamId, $postId1);
$bookmarked = $bookmarkModel->getBookmarkedPosts($liamId, 50, 0);
$bmRow = null;
foreach ($bookmarked as $row) { if ((int)$row['id'] === $postId1) { $bmRow = $row; break; } }
assertTest("Bookmarked post shows real like_count", $bmRow !== null && (int)$bmRow['like_count'] === 2);
assertTest("Bookmarked post shows viewer is_liked", $bmRow !== null && (int)$bmRow['is_liked'] === 1);
assertTest("Bookmarked rows carry is_announcement (audit F6)", $bmRow !== null && array_key_exists('is_announcement', $bmRow));
assertTest("Bookmarked rows carry author_email for the handle (audit F6)", $bmRow !== null && !empty($bmRow['author_email']));

// --- Audit C9: cached rows + live counts/viewer state ---
echo PHP_EOL . "--- FEED CACHE: LIVE STATE (audit C9) ---" . PHP_EOL;
require_once __DIR__ . '/../classes/Follow.php';
require_once __DIR__ . '/../classes/Reply.php';
$c9Post = $postModel->create($pennyId, "C9 cache probe");
$c9Find = static function (array $rows, int $id) {
    foreach ($rows as $r) { if ((int)$r['id'] === $id) { return $r; } }
    return null;
};
$postModel->getFeed(10, 0, 0); // warm the shared global cache
assertTest("Global feed cache entry exists", $cache->get("feed_global_10_0") !== null);
$likeModel->add($liamId, (int)$c9Post);
assertTest("A like does not flush the global feed cache", $cache->get("feed_global_10_0") !== null);
$c9Row = $c9Find($postModel->getFeed(10, 0, $liamId), (int)$c9Post);
assertTest("Cached feed still shows the new like_count live", $c9Row !== null && $c9Row['like_count'] === 1);
assertTest("Cached feed shows is_liked for the liker", $c9Row !== null && $c9Row['is_liked'] === 1);
$c9Other = $c9Find($postModel->getFeed(10, 0, $farhanId), (int)$c9Post);
assertTest("Same cached page shows is_liked=0 for another viewer", $c9Other !== null && $c9Other['is_liked'] === 0);
(new Reply($db))->create($farhanId, (int)$c9Post, "C9 reply");
$c9Row = $c9Find($postModel->getFeed(10, 0, $liamId), (int)$c9Post);
assertTest("Cached feed shows the new reply_count live", $c9Row !== null && $c9Row['reply_count'] === 1);
(new Bookmark($db))->add($liamId, (int)$c9Post);
$c9Row = $c9Find($postModel->getFeed(10, 0, $liamId), (int)$c9Post);
assertTest("Cached feed shows is_bookmarked live", $c9Row !== null && $c9Row['is_bookmarked'] === 1);
$c9Ids = array_map(fn($r) => (int)$r['id'], $postModel->getFeed(10, 0, 0));
$c9Fresh = array_map(fn($r) => (int)$r['id'], $postModel->getFeed(10, 0, 0, true));
assertTest("Live overlay preserves feed order", $c9Ids === $c9Fresh);

$postModel->getPersonalizedFeed($liamId, 10, 0);
$postModel->getPersonalizedFeed($farhanId, 10, 0);
(new Follow($db))->follow($liamId, $pennyId);
assertTest("Following clears only the follower's personalized cache",
    $cache->get(Post::personalizedCachePrefix($liamId) . "10_0") === null
    && $cache->get(Post::personalizedCachePrefix($farhanId) . "10_0") !== null);
assertTest("Follower's personalized feed now includes the followed user's post",
    $c9Find($postModel->getPersonalizedFeed($liamId, 10, 0), (int)$c9Post) !== null);

$postModel->getFeed(10, 0, 0);
$userModel->updateProfile($pennyId, "Penny Renamed {$uniq}", "");
$c9Row = $c9Find($postModel->getFeed(10, 0, 0), (int)$c9Post);
assertTest("A rename shows in the feed immediately (no stale author_name)",
    $c9Row !== null && $c9Row['author_name'] === "Penny Renamed {$uniq}");

// --- Audit C22: trending is time-windowed, with an all-time fallback ---
echo PHP_EOL . "--- TRENDING WINDOW (audit C22) ---" . PHP_EOL;
$oldTag = "oldtag_" . $uniq;
$oldPost = $postModel->create($pennyId, "Ancient #{$oldTag} #{$oldTag}x");
$db->prepare("UPDATE posts SET created_at = :t WHERE id = :id")
   ->execute([':t' => gmdate('Y-m-d H:i:s', time() - 30 * 86400), ':id' => (int)$oldPost]);
$recentTags = array_column($hashtagModel->getTrending(500), 'tag');
assertTest("A tag used only 30 days ago is not trending this week", !in_array($oldTag, $recentTags, true));
$allTime = array_column($hashtagModel->getTrending(500, 3650), 'tag');
assertTest("It still counts in a wide window", in_array($oldTag, $allTime, true));
assertTest("Recent tags are trending", in_array($tagA, $recentTags, true));

// --- Intent 003 Units 001 & 002: Model Inheritance & Cache Chain of Responsibility ---
echo PHP_EOL . "--- OOP FOUNDATION & CACHE CHAIN (Intent 003 Units 001 & 002) ---" . PHP_EOL;
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Report.php';
require_once __DIR__ . '/../classes/LoginRateLimiter.php';
require_once __DIR__ . '/../classes/DbSessionHandler.php';

$modelClasses = [
    'User', 'Post', 'Bookmark', 'Follow', 'Hashtag', 'Like',
    'Reply', 'Notification', 'Report', 'LoginRateLimiter', 'DbSessionHandler'
];
$allExtendModel = true;
foreach ($modelClasses as $cls) {
    if (!is_subclass_of($cls, Model::class)) {
        $allExtendModel = false;
        break;
    }
}
assertTest("All 11 domain model classes extend abstract Model base class",
    $allExtendModel && (new ReflectionClass(Model::class))->isAbstract());

// Probe Model base class protected helpers (run, fetchRow, fetchRows, fetchValue, findById)
$probeModel = new class($db) extends Model {
    public function probeFindUser(int $id): ?array {
        return $this->findById('users', $id);
    }
    public function probeCountUsers(): int {
        return (int)$this->fetchValue("SELECT COUNT(*) FROM users");
    }
    public function probeListUsers(int $limit): array {
        return $this->fetchRows("SELECT id, email FROM users LIMIT :lim", [':lim' => $limit]);
    }
};
$probedUser = $probeModel->probeFindUser($liamId);
assertTest("Model::findById and fetchRow retrieve existing row by primary key",
    $probedUser !== null && (int)$probedUser['id'] === $liamId && $probeModel->probeFindUser(0) === null);
assertTest("Model::fetchValue and fetchRows execute parameterized queries",
    $probeModel->probeCountUsers() >= 3 && count($probeModel->probeListUsers(2)) === 2);

assertTest("Cache drivers implement CacheDriverInterface",
    is_subclass_of(PhpRedisCacheDriver::class, CacheDriverInterface::class)
    && is_subclass_of(TinyRedisCacheDriver::class, CacheDriverInterface::class)
    && is_subclass_of(MemoryCacheDriver::class, CacheDriverInterface::class));

// Verify default chain topology: PhpRedisCacheDriver -> TinyRedisCacheDriver -> MemoryCacheDriver
$headDriver = $cache->getDriver();
$secondDriver = $headDriver ? $headDriver->getNext() : null;
$thirdDriver = $secondDriver ? $secondDriver->getNext() : null;
assertTest("Default Cache chain links PhpRedis -> TinyRedis -> Memory",
    $headDriver instanceof PhpRedisCacheDriver
    && $secondDriver instanceof TinyRedisCacheDriver
    && $thirdDriver instanceof MemoryCacheDriver
    && $thirdDriver->getNext() === null);

// Build an explicit unreachable-Redis chain and prove fallback to MemoryCacheDriver
$deadTiny = new TinyRedisCacheDriver('127.0.0.1', 6398, 0.05, 'cc_test:');
$memTail = new MemoryCacheDriver();
$deadTiny->setNext($memTail);
$fallbackCache = new Cache($deadTiny);
assertTest("Unreachable TinyRedisCacheDriver delegates to MemoryCacheDriver in chain",
    $deadTiny->isAvailable() === false
    && $fallbackCache->getBackendName() === 'memory'
    && $fallbackCache->getDriverName() === 'MemoryFallback');
assertTest("Chain fallback get/set/increment/delete work end-to-end when Redis is down",
    $fallbackCache->set('chain_k', ['ok' => true], 60) === true
    && $fallbackCache->get('chain_k') === ['ok' => true]
    && $fallbackCache->getCounter('chain_cnt') === null
    && (function () use ($fallbackCache) {
        $fallbackCache->setCounter('chain_cnt', 10, 60);
        return $fallbackCache->incrementCounter('chain_cnt') === 11;
    })()
    && $fallbackCache->delete('chain_k') === true
    && $fallbackCache->get('chain_k') === null);

echo PHP_EOL . "=== SPRINT 4 TEST RESULTS: {$passed} / {$total} PASSED ===" . PHP_EOL;
if ($passed === $total) {
    echo ">>> ALL TESTS PASSED SUCCESSFULLY! <<<" . PHP_EOL;
}

