<?php
/**
 * Automated Unit Test Suite for Sprint 5 Post Reporting & Moderation Queue.
 * Agile SPM - CampusConnect
 *
 * Run via CLI: php tests/ReportTest.php
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Post.php';
require_once __DIR__ . '/../classes/Report.php';
require_once __DIR__ . '/../classes/Authorization.php';

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$postModel = new Post($db);
$reportModel = new Report($db);

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

echo "=== SPRINT 5 POST REPORTING & MODERATION TEST SUITE ===" . PHP_EOL . PHP_EOL;

// 1. Setup Test Users and Posts
$uniq = time() . '_' . rand(1000, 9999);
$password = "StrongPass123!";

$userModel->register("Author Alice {$uniq}", "alice_{$uniq}@nu.edu.pk", $password);
$userModel->register("Reporter Bob {$uniq}", "bob_{$uniq}@nu.edu.pk", $password);
$userModel->register("Reporter Charlie {$uniq}", "charlie_{$uniq}@nu.edu.pk", $password);
$userModel->register("Moderator Mary {$uniq}", "mary_{$uniq}@nu.edu.pk", $password);

$alice = $userModel->verifyPassword("alice_{$uniq}@nu.edu.pk", $password);
$bob   = $userModel->verifyPassword("bob_{$uniq}@nu.edu.pk", $password);
$charlie = $userModel->verifyPassword("charlie_{$uniq}@nu.edu.pk", $password);
$mary  = $userModel->verifyPassword("mary_{$uniq}@nu.edu.pk", $password);

$aliceId = (int)$alice['id'];
$bobId   = (int)$bob['id'];
$charlieId = (int)$charlie['id'];
$maryId  = (int)$mary['id'];

// Promote Mary to system_admin
$userModel->setRole($maryId, Authorization::ROLE_SYSTEM_ADMIN);

// Create posts by Alice
$post1Id = $postModel->create($aliceId, "This is Alice's first controversial post #discussion");
$post2Id = $postModel->create($aliceId, "This is Alice's second post regarding campus rules #rules");

// --- SECTION 1: VALIDATION & WHITELIST ---
echo "--- REPORT VALIDATION & REASON WHITELIST ---" . PHP_EOL;

assertTest("Report::isValidReason accepts 'spam'", Report::isValidReason('spam') === true);
assertTest("Report::isValidReason accepts 'harassment'", Report::isValidReason('harassment') === true);
assertTest("Report::isValidReason rejects unknown reason", Report::isValidReason('offensive_language') === false);
assertTest("Report::validate rejects unknown reason", Report::validate('bad_reason')['valid'] === false);
assertTest("Report::validate rejects detail over 500 characters", Report::validate('spam', str_repeat('a', 501))['valid'] === false);
assertTest("Report::validate accepts detail with exactly 500 characters", Report::validate('spam', str_repeat('a', 500))['valid'] === true);
assertTest("Report::validate accepts empty/null detail", Report::validate('spam', null)['valid'] === true);

// --- SECTION 2: CREATE & DUPLICATE CHECKS ---
echo PHP_EOL . "--- REPORT CREATION & DOMAIN RULES ---" . PHP_EOL;

assertTest("alreadyReported returns false before reporting", $reportModel->alreadyReported($bobId, $post1Id) === false);

$repResult1 = $reportModel->create($bobId, $post1Id, 'spam', 'Repeated spam advertisements.');
assertTest("Create report with valid parameters succeeds", $repResult1['success'] === true && $repResult1['report_id'] > 0);
$report1Id = (int)$repResult1['report_id'];

assertTest("alreadyReported returns true after reporting", $reportModel->alreadyReported($bobId, $post1Id) === true);

$repDuplicate = $reportModel->create($bobId, $post1Id, 'harassment', 'Duplicate attempt.');
assertTest("Duplicate report by same user on same post is blocked", $repDuplicate['success'] === false);

$repOwnPost = $reportModel->create($aliceId, $post1Id, 'spam', 'Reporting my own post.');
assertTest("Reporting own post is blocked", $repOwnPost['success'] === false);

$repNonExistent = $reportModel->create($bobId, 999999, 'spam');
assertTest("Reporting non-existent post is rejected", $repNonExistent['success'] === false);

$repResult2 = $reportModel->create($charlieId, $post1Id, 'misinformation', 'False university schedule.');
assertTest("Second user can report the same post", $repResult2['success'] === true);

assertTest("countForPost returns exact report count (2)", $reportModel->countForPost($post1Id) === 2);
assertTest("getReportedPostIds returns reported post ID", in_array($post1Id, $reportModel->getReportedPostIds($bobId), true));

// Create report on post2 for dismiss testing
$repResult3 = $reportModel->create($bobId, $post2Id, 'other', 'Minor formatting issue.');
$report3Id = (int)$repResult3['report_id'];

// --- SECTION 3: LISTING & QUEUE ---
echo PHP_EOL . "--- MODERATION QUEUE LISTING ---" . PHP_EOL;

$pending = $reportModel->listPending();
assertTest("listPending returns pending reports", count($pending) >= 3);
assertTest("listPending row includes author and reporter names", !empty($pending[0]['post_author_name']) && !empty($pending[0]['reporter_name']));
assertTest("listPending row includes post_report_count", isset($pending[0]['post_report_count']));

// --- SECTION 4: RBAC PERMISSIONS ---
echo PHP_EOL . "--- REPORT RBAC PERMISSIONS ---" . PHP_EOL;

assertTest("Student holds report.create permission", Authorization::can(Authorization::ROLE_STUDENT, 'report.create') === true);
assertTest("Society admin holds report.create permission", Authorization::can(Authorization::ROLE_SOCIETY_ADMIN, 'report.create') === true);
assertTest("System admin holds report.create permission", Authorization::can(Authorization::ROLE_SYSTEM_ADMIN, 'report.create') === true);
assertTest("Student DOES NOT hold report.review permission", Authorization::can(Authorization::ROLE_STUDENT, 'report.review') === false);
assertTest("Society admin DOES NOT hold report.review permission", Authorization::can(Authorization::ROLE_SOCIETY_ADMIN, 'report.review') === false);
assertTest("System admin holds report.review permission", Authorization::can(Authorization::ROLE_SYSTEM_ADMIN, 'report.review') === true);

// --- SECTION 5: ADMIN REVIEW WORKFLOWS ---
echo PHP_EOL . "--- ADMIN REVIEW WORKFLOWS (DISMISS & ACTION) ---" . PHP_EOL;

// 1. Dismiss flow on report3 (post2)
$dismissResult = $reportModel->review($report3Id, 'dismiss', $maryId);
assertTest("Admin dismiss review succeeds", $dismissResult['success'] === true);

$dismissedRow = $reportModel->getById($report3Id);
assertTest("Dismissed report has status 'dismissed'", $dismissedRow['status'] === 'dismissed');
assertTest("Dismissed report records reviewer ID", (int)$dismissedRow['reviewed_by'] === $maryId);
assertTest("Post remains active after report dismissal", $postModel->getById($post2Id) !== false);

// 2. Action (delete post) flow on report1 (post1)
$actionResult = $reportModel->review($report1Id, 'action', $maryId);
assertTest("Admin action (delete post) review succeeds", $actionResult['success'] === true);

$actionedRow = $reportModel->getById($report1Id);
assertTest("Actioned report has status 'actioned'", $actionedRow['status'] === 'actioned');
assertTest("Actioned report records reviewer ID", (int)$actionedRow['reviewed_by'] === $maryId);

// Check that the post is now soft-deleted
$deletedPost = $postModel->getById($post1Id);
assertTest("Violating post is soft-deleted and removed from getById", $deletedPost === false);

// Check that second report on same post is also actioned
$report2Row = $reportModel->getById((int)$repResult2['report_id']);
assertTest("Co-reports on deleted post are also actioned", $report2Row['status'] === 'actioned');

// Reviewing already reviewed report fails
$reReview = $reportModel->review($report1Id, 'dismiss', $maryId);
assertTest("Reviewing already reviewed report is rejected", $reReview['success'] === false);

// Reviewed reports list includes the resolved reports
$reviewedList = $reportModel->listReviewed();
assertTest("listReviewed includes dismissed and actioned reports", count($reviewedList) >= 2);

echo PHP_EOL . "=================================================" . PHP_EOL;
echo "REPORT TEST SUMMARY: {$passed} / {$total} Passed" . PHP_EOL;
echo "=================================================" . PHP_EOL;

if ($passed === $total) {
    echo ">>> ALL REPORT TESTS PASSED SUCCESSFULLY! <<<" . PHP_EOL;
    exit(0);
} else {
    echo ">>> SOME REPORT TESTS FAILED! <<<" . PHP_EOL;
    exit(1);
}
