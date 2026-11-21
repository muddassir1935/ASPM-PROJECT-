<?php
/**
 * AdminController & NotificationController Test Suite
 * (intent 003, unit 005-controllers-admin, EF-3c)
 *
 * Verifies that admin management, report moderation, and notification handling
 * are encapsulated in AdminController and NotificationController, and that the
 * action pages (admin_action.php, report_action.php, report_review.php,
 * notification_action.php) are thin entry points that delegate.
 *
 * Run via CLI: php tests/AdminControllerTest.php
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Post.php';
require_once __DIR__ . '/../classes/Report.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Csrf.php';
require_once __DIR__ . '/../classes/AdminController.php';
require_once __DIR__ . '/../classes/NotificationController.php';

$csrfHelper = new Csrf();
$csrf = $csrfHelper->token();
$passed = 0;
$total = 0;

function assertAdminCtrl(string $name, bool $condition, ?string $detail = null): void {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo " [PASS] Test {$total}: {$name}\n";
    } else {
        echo " [FAIL] Test {$total}: {$name}" . ($detail ? " ({$detail})" : '') . "\n";
    }
}

echo "=== ADMIN & NOTIFICATION CONTROLLER TEST SUITE (UNIT 005 / EF-3c) ===\n\n";

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$postModel = new Post($db);
$reportModel = new Report($db);
$notifModel = new Notification($db);

$uniq = time() . '_' . rand(1000, 9999);
$userModel->register("Admin Alice {$uniq}", "admin_alice_{$uniq}@nu.edu.pk", 'StrongPass123!');
$userModel->register("Student Sam {$uniq}", "student_sam_{$uniq}@nu.edu.pk", 'StrongPass123!');
$userModel->register("Target Tom {$uniq}", "target_tom_{$uniq}@nu.edu.pk", 'StrongPass123!');

$aliceData = $userModel->verifyPassword("admin_alice_{$uniq}@nu.edu.pk", 'StrongPass123!');
$samData = $userModel->verifyPassword("student_sam_{$uniq}@nu.edu.pk", 'StrongPass123!');
$tomData = $userModel->verifyPassword("target_tom_{$uniq}@nu.edu.pk", 'StrongPass123!');

$aliceId = (int)$aliceData['id'];
$samId = (int)$samData['id'];
$tomId = (int)$tomData['id'];

// Make Alice a system_admin
$userModel->setRole($aliceId, Authorization::ROLE_SYSTEM_ADMIN);

$adminUser = ['id' => $aliceId, 'name' => 'Alice Admin', 'role' => Authorization::ROLE_SYSTEM_ADMIN];
$studentUser = ['id' => $samId, 'name' => 'Sam Student', 'role' => Authorization::ROLE_STUDENT];

$adminCtrl = AdminController::fromConnection($db, $adminUser);
$studentCtrl = AdminController::fromConnection($db, $studentUser);
$guestCtrl = AdminController::fromConnection($db, null);

$okToken = ['csrf_token' => $csrf];

echo "--- ADMIN ACTION GUARDS & PERMISSIONS ---" . PHP_EOL;

// 1. Guest access is redirected to login.php
$r = $guestCtrl->handleAdminAction('POST', $okToken + ['action' => 'assign_role']);
assertAdminCtrl("Guest access redirects to login.php",
    $r['result'] === null && $r['redirect'] === 'login.php');

// 2. Student (lacks user.manage) is redirected to index.php
$r = $studentCtrl->handleAdminAction('POST', $okToken + ['action' => 'assign_role']);
assertAdminCtrl("Student (lacks user.manage) is denied with error and redirected to index.php",
    $r['result'] === ['success' => false, 'message' => 'You do not have permission to manage users.']
        && $r['redirect'] === 'index.php');

// 3. Non-POST method rejected
$r = $adminCtrl->handleAdminAction('GET', $okToken + ['action' => 'assign_role']);
assertAdminCtrl("Non-POST method rejected with Invalid request",
    $r['result'] === ['success' => false, 'message' => 'Invalid request.']);

// 4. Invalid CSRF token rejected
$r = $adminCtrl->handleAdminAction('POST', ['csrf_token' => 'invalid', 'action' => 'assign_role']);
assertAdminCtrl("Invalid CSRF token rejected with Security token expired",
    $r['result'] === ['success' => false, 'message' => 'Security token expired. Please try again.']);

echo PHP_EOL . "--- PASSWORD RESET VIA ADMIN CONTROLLER ---" . PHP_EOL;

// 5. Admin reset on own account blocked
$r = $adminCtrl->handleAdminAction('POST', $okToken + ['action' => 'reset_password', 'user_id' => $aliceId]);
assertAdminCtrl("Admin cannot reset their own password here",
    $r['result'] === ['success' => false, 'message' => 'Use Change password on your own profile instead.']);

// 6. Admin reset on non-existent user
$r = $adminCtrl->handleAdminAction('POST', $okToken + ['action' => 'reset_password', 'user_id' => 999999]);
assertAdminCtrl("Admin reset on non-existent user returns User not found",
    $r['result'] === ['success' => false, 'message' => 'User not found.']);

// 7. Successful password reset (also revokes the target's sessions)
$db->prepare("INSERT INTO sessions (id, user_id, data, last_activity) VALUES (:sid, :uid, 'x', :now)")
    ->execute(['sid' => "admintest_{$uniq}", 'uid' => $tomId, 'now' => time()]);
$r = $adminCtrl->handleAdminAction('POST', $okToken + ['action' => 'reset_password', 'user_id' => $tomId]);
assertAdminCtrl("Admin successfully resets target user password and gets temporary password",
    $r['result']['success'] === true && str_contains($r['result']['message'], 'Temporary password for Target Tom'));
$tomSessionsStmt = $db->prepare("SELECT COUNT(*) FROM sessions WHERE user_id = :uid");
$tomSessionsStmt->execute(['uid' => $tomId]);
assertAdminCtrl("Admin reset revokes the target user's sessions",
    (int)$tomSessionsStmt->fetchColumn() === 0);

echo PHP_EOL . "--- ROLE ASSIGNMENT VIA ADMIN CONTROLLER ---" . PHP_EOL;

// 8. Admin cannot change their own role
$r = $adminCtrl->handleAdminAction('POST', $okToken + ['action' => 'assign_role', 'user_id' => $aliceId, 'role' => 'student']);
assertAdminCtrl("Admin cannot change their own role",
    $r['result'] === ['success' => false, 'message' => 'You cannot change your own role.']);

// 9. Invalid role rejected
$r = $adminCtrl->handleAdminAction('POST', $okToken + ['action' => 'assign_role', 'user_id' => $tomId, 'role' => 'superman']);
assertAdminCtrl("Invalid role rejected",
    $r['result'] === ['success' => false, 'message' => 'Invalid role selected.']);

// 10. Valid role promotion
$r = $adminCtrl->handleAdminAction('POST', $okToken + ['action' => 'assign_role', 'user_id' => $tomId, 'role' => 'society_admin']);
assertAdminCtrl("Admin successfully promotes user to society_admin",
    $r['result']['success'] === true && str_contains($r['result']['message'], 'is now Society Admin'));

$tomUpdated = $userModel->getById($tomId);
assertAdminCtrl("Target user's role is updated in database",
    $tomUpdated['role'] === Authorization::ROLE_SOCIETY_ADMIN);

// 11. Unknown action
$r = $adminCtrl->handleAdminAction('POST', $okToken + ['action' => 'do_magic']);
assertAdminCtrl("Unknown admin action returns error",
    $r['result'] === ['success' => false, 'message' => 'Unknown action.']);

echo PHP_EOL . "--- REPORTING & MODERATION REVIEW VIA ADMIN CONTROLLER ---" . PHP_EOL;

$testPostId = (int)$postModel->create($tomId, "Post to be reported {$uniq}");

// 12. Student reports post
$r = $studentCtrl->handleReport('POST', $okToken + ['post_id' => $testPostId, 'reason' => 'spam', 'return' => 'index']);
assertAdminCtrl("Student can submit report via handleReport()",
    $r['result']['success'] === true && $r['redirect'] === 'index.php');

$reportId = (int)($r['result']['report_id'] ?? 0);
assertAdminCtrl("Report ID returned from handleReport()", $reportId > 0);

// 13. Student denied report review
$r = $studentCtrl->handleReportReview('POST', $okToken + ['report_id' => $reportId, 'action' => 'dismiss']);
assertAdminCtrl("Student denied report review (lacks report.review)",
    $r['result']['success'] === false && $r['redirect'] === 'index.php');

// 14. Admin reviews report (dismiss)
$r = $adminCtrl->handleReportReview('POST', $okToken + ['report_id' => $reportId, 'action' => 'dismiss']);
assertAdminCtrl("Admin successfully reviews/dismisses report via handleReportReview()",
    $r['result']['success'] === true && $r['redirect'] === 'admin.php#reports');

echo PHP_EOL . "--- NOTIFICATION CONTROLLER (AJAX & ENDPOINTS) ---" . PHP_EOL;

$notifCtrl = NotificationController::fromConnection($db, $studentUser);
$guestNotifCtrl = NotificationController::fromConnection($db, null);

// 15. Guest blocked from notification endpoint -> 401
$r = $guestNotifCtrl->handle('GET', ['action' => 'count'], []);
assertAdminCtrl("Guest access to notification endpoint returns 401",
    $r['status'] === 401 && $r['body']['message'] === 'Login required.');

// 16. Unread count query -> 200
$r = $notifCtrl->handle('GET', ['action' => 'count'], []);
assertAdminCtrl("GET count returns 200 and unread_count integer",
    $r['status'] === 200 && isset($r['body']['unread_count']));

// 17. POST without CSRF -> 403
$r = $notifCtrl->handle('POST', [], ['action' => 'mark_all_read']);
assertAdminCtrl("POST notification without CSRF returns 403",
    $r['status'] === 403 && str_contains($r['body']['message'], 'token expired'));

// 18. POST mark_all_read -> 200
$r = $notifCtrl->handle('POST', [], $okToken + ['action' => 'mark_all_read']);
assertAdminCtrl("POST mark_all_read succeeds with 200",
    $r['status'] === 200 && $r['body']['success'] === true);

// 19. Forwarding from AdminController
$r = $adminCtrl->handleNotification('GET', ['action' => 'count'], []);
assertAdminCtrl("AdminController::handleNotification() forwards cleanly to NotificationController",
    $r['status'] === 200 && isset($r['body']['unread_count']));

echo PHP_EOL . "--- PAGES DELEGATE (ENTRY POINTS ARE THIN) ---" . PHP_EOL;

$root = __DIR__ . '/..';
$adminActionSrc = file_get_contents("{$root}/admin_action.php");
assertAdminCtrl("admin_action.php delegates to AdminController::handleAdminAction()",
    str_contains($adminActionSrc, '->handleAdminAction($_SERVER[\'REQUEST_METHOD\'], $_POST)'));

$reportActionSrc = file_get_contents("{$root}/report_action.php");
assertAdminCtrl("report_action.php delegates to AdminController::handleReport()",
    str_contains($reportActionSrc, '->handleReport($_SERVER[\'REQUEST_METHOD\'], $_POST)'));

$reportReviewSrc = file_get_contents("{$root}/report_review.php");
assertAdminCtrl("report_review.php delegates to AdminController::handleReportReview()",
    str_contains($reportReviewSrc, '->handleReportReview($_SERVER[\'REQUEST_METHOD\'], $_POST)'));

$notifActionSrc = file_get_contents("{$root}/notification_action.php");
assertAdminCtrl("notification_action.php delegates to NotificationController::handle()",
    str_contains($notifActionSrc, '->handle($_SERVER[\'REQUEST_METHOD\'], $_GET, $_POST)'));

// Close session safely
session_write_close();

echo PHP_EOL . "=================================================\n";
echo "ADMIN CONTROLLER TEST SUMMARY: {$passed} / {$total} Passed\n";
echo "=================================================\n";

if ($passed !== $total) {
    exit(1);
}
