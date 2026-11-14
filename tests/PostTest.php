<?php
/**
 * Automated Unit Test Suite for Post Model and PostController (Sprint 2)
 * Agile SPM - CampusConnect
 * 
 * Run via CLI: php tests/PostTest.php
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Post.php';
require_once __DIR__ . '/../classes/PostController.php';


$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$postModel = new Post($db);

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
$emailA = "author_{$uniq}@nu.edu.pk";
$emailB = "intruder_{$uniq}@nu.edu.pk";
$emailAdmin = "admin_{$uniq}@nu.edu.pk";
$password = "StrongPass123!";

$userModel->register("Alice Author", $emailA, $password);
$userModel->register("Bob Intruder", $emailB, $password);
$userModel->register("Admin User", $emailAdmin, $password);

$userA = $userModel->verifyPassword($emailA, $password);
$userB = $userModel->verifyPassword($emailB, $password);
$userAdmin = $userModel->verifyPassword($emailAdmin, $password);

// Set admin role for userAdmin
$db->exec("UPDATE users SET role = 'system_admin' WHERE id = " . (int)$userAdmin['id']);
$userAdmin['role'] = 'system_admin';

$authorId = (int)$userA['id'];
$intruderId = (int)$userB['id'];
$adminId = (int)$userAdmin['id'];

// --- VALIDATION TESTS ---
echo "--- POST VALIDATION TESTS ---" . PHP_EOL;

$valEmpty = $postModel->validateContent("");
assertTest("Reject empty string post", $valEmpty['valid'] === false);

$valWhitespace = $postModel->validateContent("     \n\t   ");
assertTest("Reject whitespace-only post", $valWhitespace['valid'] === false);

$valOver = $postModel->validateContent(str_repeat("A", 281));
assertTest("Reject post exceeding 280 characters", $valOver['valid'] === false);

$valExact = $postModel->validateContent(str_repeat("A", 280));
assertTest("Accept post with exactly 280 characters", $valExact['valid'] === true && mb_strlen($valExact['clean']) === 280);

$valNormal = $postModel->validateContent("Hello FAST NUCES! Registration deadline is tomorrow.");
assertTest("Accept valid post text", $valNormal['valid'] === true);


// --- POST CREATION & RETRIEVAL TESTS ---
echo PHP_EOL . "--- POST CRUD & FEED TESTS ---" . PHP_EOL;

$postId1 = $postModel->create($authorId, "First campus announcement from Alice!");
assertTest("Create valid post returns numeric ID", is_int($postId1) && $postId1 > 0);

$postIdEmpty = $postModel->create($authorId, "");
assertTest("Creation fails with empty content", $postIdEmpty === false);

$postIdInvalidUser = $postModel->create(-1, "Some post");
assertTest("Creation fails with invalid user ID", $postIdInvalidUser === false);

// Retrieve by ID
$fetched = $postModel->getById($postId1);
assertTest("Retrieve post by ID includes author name and relative time", 
    $fetched !== false && 
    $fetched['author_name'] === "Alice Author" && 
    isset($fetched['time_ago'])
);

// Timeline Feed
$feed = $postModel->getFeed(10, 0);
assertTest("Feed returns non-empty list of posts with authors",
    is_array($feed) && count($feed) >= 1 && isset($feed[0]['author_name'])
);


// --- UPDATE & AUTHORIZATION TESTS ---
echo PHP_EOL . "--- UPDATE & AUTHORIZATION TESTS ---" . PHP_EOL;

$updateSuccess = $postModel->update($postId1, $authorId, "Updated campus announcement text.");
assertTest("Author can successfully update their own post", $updateSuccess === true);

$updatedFetch = $postModel->getById($postId1);
assertTest("Updated content is persisted in database", $updatedFetch['content'] === "Updated campus announcement text.");

$intruderUpdate = $postModel->update($postId1, $intruderId, "Hacked by Bob!");
assertTest("Intruder cannot update another user's post (Authorization check)", $intruderUpdate === false);

$emptyUpdate = $postModel->update($postId1, $authorId, "   ");
assertTest("Update rejected when content is whitespace only", $emptyUpdate === false);


// --- CONTROLLER LAYER TESTS ---
echo PHP_EOL . "--- CONTROLLER LAYER TESTS ---" . PHP_EOL;

$authorController = new PostController($postModel, $userA);
$res1 = $authorController->handleCreate("Post created via PostController.");
assertTest("Controller handleCreate returns success structure with post_id", 
    $res1['success'] === true && is_int($res1['post_id'])
);

$resEmpty = $authorController->handleCreate("");
assertTest("Controller handleCreate rejects empty post with error message", 
    $resEmpty['success'] === false && !empty($resEmpty['message'])
);

$unauthController = new PostController($postModel, null);
$resUnauth = $unauthController->handleCreate("Should fail due to no login");
assertTest("Controller rejects unauthenticated post attempts", 
    $resUnauth['success'] === false && strpos($resUnauth['message'], 'Authentication required') !== false
);

$timelinePage = $authorController->getTimelineFeed(1, 10);
assertTest("Controller getTimelineFeed returns structured pagination response", 
    isset($timelinePage['posts']) && isset($timelinePage['total']) && $timelinePage['total'] >= 2
);


// --- DELETE & PRIVILEGE TESTS ---
echo PHP_EOL . "--- DELETION & PRIVILEGE TESTS ---" . PHP_EOL;

$postId2 = $postModel->create($authorId, "Post to be deleted by intruder (should fail)");

$intruderDelete = $postModel->delete($postId2, $intruderId, false);
assertTest("Intruder cannot delete another user's post", $intruderDelete === false);

$authorDelete = $postModel->delete($postId2, $authorId, false);
assertTest("Author can delete their own post", $authorDelete === true);
assertTest("Deleted post no longer exists in database", $postModel->getById($postId2) === false);

// Admin deletion of foreign post
$postId3 = $postModel->create($authorId, "Inappropriate post requiring admin moderation");
$adminDelete = $postModel->delete($postId3, $adminId, true);
assertTest("Admin can moderate/delete any post (Role-based access)", $adminDelete === true);


// --- SOFT-DELETE TESTS (WBS 2.3.6) ---
echo PHP_EOL . "--- SOFT-DELETE TESTS ---" . PHP_EOL;

$postId4 = $postModel->create($authorId, "Post that will be soft deleted");
$countBeforeDelete = $postModel->countByUser($authorId);
$softDelete = $postModel->delete($postId4, $authorId, false);
assertTest("Soft-delete returns true for the author", $softDelete === true);

// The row must still exist in the database, with deleted_at set
$stmtSoft = $db->prepare("SELECT deleted_at FROM posts WHERE id = :id");
$stmtSoft->bindParam(':id', $postId4, PDO::PARAM_INT);
$stmtSoft->execute();
$softRow = $stmtSoft->fetch(PDO::FETCH_ASSOC);
assertTest("Soft-deleted row still exists in database with deleted_at set",
    $softRow !== false && !empty($softRow['deleted_at']));

// ...but it stays hidden from reads, feeds and counts
assertTest("Soft-deleted post is hidden from getById", $postModel->getById($postId4) === false);
assertTest("Soft-deleted post is excluded from countByUser",
    $postModel->countByUser($authorId) === $countBeforeDelete - 1);
$feedAfterDelete = $postModel->getFeed(50, 0);
$feedIds = array_map(fn($p) => (int)$p['id'], $feedAfterDelete);
assertTest("Soft-deleted post is excluded from the feed", !in_array($postId4, $feedIds, true));
assertTest("Soft-deleted post cannot be updated",
    $postModel->update($postId4, $authorId, "Trying to revive a deleted post") === false);
assertTest("Soft-deleted post cannot be deleted twice",
    $postModel->delete($postId4, $authorId, false) === false);


echo PHP_EOL . "=================================================" . PHP_EOL;
echo "TEST SUMMARY: {$passed} / {$total} Passed" . PHP_EOL;
echo "=================================================" . PHP_EOL;

if ($passed === $total) {
    exit(0);
} else {
    exit(1);
}
