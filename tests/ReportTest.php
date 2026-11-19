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
require_once __DIR__ . '/../classes/Notification.php';
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
assertTest("listPending honors limit and offset",
    count($reportModel->listPending(1, 0)) === 1
        && count($reportModel->listPending(1, 1)) === 1
        && (int)$reportModel->listPending(1, 0)[0]['id'] !== (int)$reportModel->listPending(1, 1)[0]['id']);

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
$notifications = new Notification($db);
$bobReportNotes = array_filter($notifications->listAll($bobId, 50),
    fn($row) => str_contains($row['message'], 'report has been reviewed'));
assertTest("Dismissed report notifies its reporter", count($bobReportNotes) === 1);

$dismissedRow = $reportModel->getById($report3Id);
assertTest("Dismissed report has status 'dismissed'", $dismissedRow['status'] === 'dismissed');
assertTest("Dismissed report records reviewer ID", (int)$dismissedRow['reviewed_by'] === $maryId);
assertTest("Post remains active after report dismissal", $postModel->getById($post2Id) !== false);

// 2. Action (delete post) flow on report1 (post1)
$actionResult = $reportModel->review($report1Id, 'action', $maryId);
assertTest("Admin action (delete post) review succeeds", $actionResult['success'] === true);
$authorModerationNotes = array_filter($notifications->listAll($aliceId, 50),
    fn($row) => str_contains($row['message'], 'removed by administration'));
assertTest("Actioned post notifies its author", count($authorModerationNotes) === 1);
$bobReportNotes = array_filter($notifications->listAll($bobId, 50),
    fn($row) => str_contains($row['message'], 'report has been reviewed'));
$charlieReportNotes = array_filter($notifications->listAll($charlieId, 50),
    fn($row) => str_contains($row['message'], 'report has been reviewed'));
assertTest("Actioned reports notify each reporter", count($bobReportNotes) === 2 && count($charlieReportNotes) === 1);

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

// --- SECTION 6: MODERATION NOTIFICATIONS & PAGINATION (UNIT 003) ---
echo PHP_EOL . "--- MODERATION NOTIFICATIONS & PAGINATION (UNIT 003) ---" . PHP_EOL;

require_once __DIR__ . '/../classes/Notification.php';
$notifModel = new Notification($db);

// 1. Author receives notification when post is soft-deleted by moderator
$aliceNotifs = $notifModel->listAll($aliceId);
$authorNotified = false;
foreach ($aliceNotifs as $n) {
    if ($n['type'] === 'moderation' && strpos($n['message'], 'violating campus policies') !== false) {
        $authorNotified = true;
        break;
    }
}
assertTest("Post author receives notification when post is soft-deleted by moderator", $authorNotified === true);

// 2. Reporter receives notification when report is dismissed
$bobNotifs = $notifModel->listAll($bobId);
$reporterDismissNotified = false;
foreach ($bobNotifs as $n) {
    if ($n['type'] === 'moderation' && strpos($n['message'], 'reviewed') !== false) {
        $reporterDismissNotified = true;
        break;
    }
}
assertTest("Reporting user receives notification when report is dismissed", $reporterDismissNotified === true);

// 3. Reporter receives notification when report is actioned
$charlieNotifs = $notifModel->listAll($charlieId);
$reporterActionNotified = false;
foreach ($charlieNotifs as $n) {
    if ($n['type'] === 'moderation' && strpos($n['message'], 'reviewed') !== false) {
        $reporterActionNotified = true;
        break;
    }
}
assertTest("Reporting user receives notification when report is actioned", $reporterActionNotified === true);

// 4. Admin Reports Queue pagination support (> 20 items)
for ($i = 1; $i <= 22; $i++) {
    $pId = $postModel->create($aliceId, "Bulk post {$i} {$uniq}");
    $reportModel->create($bobId, $pId, 'spam', "Bulk report {$i}");
}
$pendingTotal = $reportModel->countPending();
assertTest("Pending report count exceeds 20 items ({$pendingTotal})", $pendingTotal > 20);

require_once __DIR__ . '/../includes/social_widgets.php';
$page1Clamped = clampPage(1, $pendingTotal, 20);
$page2Clamped = clampPage(2, $pendingTotal, 20);
assertTest("clampPage correctly resolves page 1 and page 2 for >20 reports", $page1Clamped === 1 && $page2Clamped === 2);

ob_start();
renderPager('admin.php', 1, 1 * 20 < $pendingTotal, 'rpage');
$pagerPage1Html = ob_get_clean();
assertTest("renderPager renders Next link for page 1 when total > 20", strpos($pagerPage1Html, 'rpage=2') !== false);

ob_start();
renderPager('admin.php', 2, 2 * 20 < $pendingTotal, 'rpage');
$pagerPage2Html = ob_get_clean();
assertTest("renderPager renders Previous link on page 2", strpos($pagerPage2Html, 'rpage=1') !== false);

// --- SECTION 7: DB-LEVEL CONSTRAINTS (intent 003, unit 008 / EF-5) ---
echo PHP_EOL . "--- REPORT COLUMN CONSTRAINTS (DB) ---" . PHP_EOL;

// Raw inserts bypass Report::validate(), so only the DB constraint can stop these.
$invalidReasonRejected = false;
try {
    $db->prepare("INSERT INTO reports (reporter_id, post_id, reason, status) VALUES (:r, :p, 'offensive_language', 'pending')")
        ->execute([':r' => $charlieId, ':p' => $post2Id]);
} catch (PDOException $e) {
    $invalidReasonRejected = true;
}
assertTest("DB rejects a report reason outside the whitelist", $invalidReasonRejected === true);

$invalidStatusRejected = false;
try {
    $db->prepare("INSERT INTO reports (reporter_id, post_id, reason, status) VALUES (:r, :p, 'spam', 'reviewed')")
        ->execute([':r' => $charlieId, ':p' => $post2Id]);
} catch (PDOException $e) {
    $invalidStatusRejected = true;
}
assertTest("DB rejects a report status outside pending/dismissed/actioned", $invalidStatusRejected === true);

// 'actioned' is a real stored status (Report::STATUS_ACTIONED) -- it must pass.
$validActionedAccepted = false;
try {
    $db->prepare("INSERT INTO reports (reporter_id, post_id, reason, status) VALUES (:r, :p, 'violence', 'actioned')")
        ->execute([':r' => $charlieId, ':p' => $post2Id]);
    $validActionedAccepted = true;
} catch (PDOException $e) {
    $validActionedAccepted = false;
}
assertTest("DB accepts a report with a whitelisted reason and 'actioned' status", $validActionedAccepted === true);

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
