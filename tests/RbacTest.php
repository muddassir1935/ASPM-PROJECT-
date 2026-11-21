<?php
/**
 * Automated Unit Test Suite for Sprint 5 RBAC + security hardening:
 * Authorization permission matrix, CSRF tokens, login rate-limit,
 * admin role assignment, society announcements, session revocation.
 *
 * Run via CLI: php tests/RbacTest.php
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Post.php';
require_once __DIR__ . '/../classes/PostController.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Authorization.php';
require_once __DIR__ . '/../classes/Csrf.php';
require_once __DIR__ . '/../classes/LoginRateLimiter.php';
require_once __DIR__ . '/../classes/DbSessionHandler.php';

// Start the session BEFORE any output: in this VM's PHP CLI,
// headers_sent() becomes true after the first echo, and both
// $csrf->token() and $auth->startSession() then skip session_start().
// (In a web request headers are not sent yet at this point, so the
// app itself is unaffected — this is a CLI test-runner quirk only.)
$csrfHelper = new Csrf();
$csrfHelper->token();

echo "=================================================\n";
echo "   RBAC + Security Hardening Test Suite (Sprint 5)\n";
echo "=================================================\n\n";

$totalTests = 0;
$passedTests = 0;

function runTest($testName, $actualResult, $expectedResult) {
    global $totalTests, $passedTests;
    $totalTests++;
    $passed = ($actualResult === $expectedResult);
    if ($passed) {
        $passedTests++;
        echo " [PASS] " . $testName . "\n";
    } else {
        echo " [FAIL] " . $testName . " (expected " . var_export($expectedResult, true)
            . ", got " . var_export($actualResult, true) . ")\n";
    }
}

// ---------------------------------------------------------
// SECTION 1: AUTHORIZATION PERMISSION MATRIX
// ---------------------------------------------------------
echo "--- PERMISSION MATRIX (role x permission) ---\n";

$authz = new Authorization();
$matrix = [
    // permission => [student, society_admin, system_admin]
    'post.create' => [true, true, true],
    'post.edit_own' => [true, true, true],
    'post.delete_own' => [true, true, true],
    'post.delete_any' => [false, false, true],
    'reply.create' => [true, true, true],
    'reply.delete_own' => [true, true, true],
    'reply.delete_any' => [false, false, true],
    'announcement.create' => [false, true, true],
    'announcement.delete_any' => [false, false, true],
    'user.manage' => [false, false, true],
    'role.assign' => [false, false, true],
];
$roles = ['student', 'society_admin', 'system_admin'];
foreach ($matrix as $permission => $expected) {
    foreach ($roles as $i => $role) {
        runTest("Authorization->can('{$role}', '{$permission}')",
            $authz->can($role, $permission), $expected[$i]);
    }
}

runTest("Unknown permission is denied (fail closed)",
    $authz->can('system_admin', 'does.not.exist'), false);
runTest("Logged-out / unknown role falls back to student permissions",
    $authz->can('nobody', 'post.delete_any'), false);
runTest("Unknown role can still create posts (student fallback)",
    $authz->can('nobody', 'post.create'), true);

// ---------------------------------------------------------
// SECTION 2: ROLE NORMALIZATION
// ---------------------------------------------------------
echo "\n--- ROLE NORMALIZATION ---\n";

runTest("Legacy 'admin' normalizes to system_admin",
    $authz->normalizeRole('admin'), 'system_admin');
runTest("Legacy 'society' normalizes to society_admin",
    $authz->normalizeRole('society'), 'society_admin');
runTest("Unknown role normalizes to student (least privilege)",
    $authz->normalizeRole('superuser'), 'student');
runTest("Empty role normalizes to student",
    $authz->normalizeRole(''), 'student');
runTest("isValidRole accepts the three current roles",
    $authz->isValidRole('society_admin'), true);
runTest("isValidRole rejects legacy 'admin' for assignment",
    $authz->isValidRole('admin'), false);
runTest("isValidRole rejects unknown roles",
    $authz->isValidRole('root'), false);
runTest("permissionsFor('system_admin') holds all 14 permissions",
    count($authz->permissionsFor('system_admin')), 14);
runTest("system_admin can reset passwords, student cannot",
    $authz->can('system_admin', 'user.reset_password') === true
        && $authz->can('student', 'user.reset_password') === false
        && $authz->can('society_admin', 'user.reset_password') === false,
    true);
runTest("permissionsFor('student') holds 6 permissions",
    count($authz->permissionsFor('student')), 6);
runTest("displayLabel('system_admin') is 'System Admin'",
    $authz->displayLabel('system_admin'), 'System Admin');

// ---------------------------------------------------------
// SECTION 3: CSRF TOKENS
// ---------------------------------------------------------
echo "\n--- CSRF TOKENS ---\n";

$token = $csrfHelper->token();
runTest("Csrf->token() returns a non-empty token", $token !== '', true);
runTest("The issued token validates", $csrfHelper->validate($token), true);
$injectedCsrf = new Csrf();
runTest("Injected Csrf instance issues and validates token",
    $injectedCsrf->validate($injectedCsrf->token()), true);
// Regression guard for the Sprint 5 live bug: the CSRF token lives in
// the DB-backed session, so Csrf must register the DB handler before
// session_start() — otherwise a POST handler that validates the token
// before touching Auth reads an empty file-based session and rejects
// every valid token (signup/login were broken by exactly this).
runTest("Csrf session uses the DB-backed handler",
    ini_get('session.save_handler'), 'user');
runTest("A wrong token is rejected", $csrfHelper->validate($token . 'x'), false);
runTest("An empty token is rejected", $csrfHelper->validate(''), false);
runTest("A null token is rejected", $csrfHelper->validate(null), false);
runTest("Csrf->field() embeds the current token",
    strpos($csrfHelper->field(), htmlspecialchars($token, ENT_QUOTES, 'UTF-8')) !== false, true);
$csrfHelper->rotate();
runTest("After rotation the old token is rejected", $csrfHelper->validate($token), false);
runTest("After rotation the new token validates", $csrfHelper->validate($csrfHelper->token()), true);

// ---------------------------------------------------------
// SECTION 4: LOGIN RATE LIMITER (in-memory SQLite)
// ---------------------------------------------------------
echo "\n--- LOGIN RATE LIMITER ---\n";

$memPdo = new PDO('sqlite::memory:');
$memPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$limiter = new LoginRateLimiter($memPdo);
$testEmail = 'ratelimit_test@nu.edu.pk';
$testIp = '192.0.2.10';
$now = time();

runTest("Fresh pair is not locked", $limiter->isLocked($testEmail, $testIp, $now), false);
for ($i = 1; $i <= 4; $i++) {
    $limiter->recordFailure($testEmail, $testIp, $now + $i);
}
runTest("4 failures: still not locked", $limiter->isLocked($testEmail, $testIp, $now + 5), false);
runTest("4 failures counted in window", $limiter->attemptsInWindow($testEmail, $testIp, $now + 5), 4);
$limiter->recordFailure($testEmail, $testIp, $now + 6);
runTest("5th failure locks the pair", $limiter->isLocked($testEmail, $testIp, $now + 7), true);
runTest("Lock duration is five minutes", LoginRateLimiter::LOCK_SECONDS, 300);
runTest("Lock has seconds remaining", $limiter->lockRemainingSeconds($testEmail, $testIp, $now + 7) > 0, true);
runTest("Progressive delay starts at zero", LoginRateLimiter::delayMicrosecondsForAttempts(1), 0);
runTest("Progressive delay increases after failed attempts", LoginRateLimiter::delayMicrosecondsForAttempts(4), 300000);
runTest("Progressive delay is capped at one second", LoginRateLimiter::delayMicrosecondsForAttempts(20), 1000000);
runTest("A different IP is not locked by this pair's failures",
    $limiter->isLocked($testEmail, '192.0.2.99', $now + 7), false);
runTest("A different email is not locked by this pair's failures",
    $limiter->isLocked('other@nu.edu.pk', $testIp, $now + 7), false);
runTest("Lock expires after LOCK_SECONDS",
    $limiter->isLocked($testEmail, $testIp, $now + LoginRateLimiter::LOCK_SECONDS + 10), false);
runTest("Attempts reset once the window passes",
    $limiter->attemptsInWindow($testEmail, $testIp, $now + LoginRateLimiter::WINDOW_SECONDS + 10), 0);
$limiter->recordFailure($testEmail, $testIp, $now + LoginRateLimiter::WINDOW_SECONDS + 100);
runTest("Failure after lock + window expiry starts a fresh count (not locked)",
    $limiter->isLocked($testEmail, $testIp, $now + LoginRateLimiter::WINDOW_SECONDS + 101), false);

// Audit C21: IP-wide ceiling against password spraying
$sprayIp = '198.51.100.7';
for ($i = 0; $i < LoginRateLimiter::IP_MAX_ATTEMPTS - 1; $i++) {
    $limiter->recordFailure("spray{$i}@nu.edu.pk", $sprayIp, $now);
}
runTest("Spraying: 19 failures across 19 emails is not yet locked",
    $limiter->isLocked('fresh.victim@nu.edu.pk', $sprayIp, $now + 1), false);
$limiter->recordFailure("spray_last@nu.edu.pk", $sprayIp, $now + 1);
runTest("Spraying: 20th failure locks the IP for every email",
    $limiter->isLocked('fresh.victim@nu.edu.pk', $sprayIp, $now + 2), true);
runTest("Spraying lock does not affect other IPs",
    $limiter->isLocked('fresh.victim@nu.edu.pk', '198.51.100.8', $now + 2), false);
runTest("Spraying lock reports remaining time",
    $limiter->lockRemainingSeconds('fresh.victim@nu.edu.pk', $sprayIp, $now + 2) > 0, true);
runTest("Spraying lock expires after LOCK_SECONDS",
    $limiter->isLocked('fresh.victim@nu.edu.pk', $sprayIp, $now + 2 + LoginRateLimiter::LOCK_SECONDS), false);
$limiter->recordSuccess($testEmail, $testIp);
runTest("Success clears the failure history",
    $limiter->attemptsInWindow($testEmail, $testIp, $now + 22), 0);
$resetEmail = 'ratelimit_reset@nu.edu.pk';
for ($i = 1; $i <= 4; $i++) {
    $limiter->recordFailure($resetEmail, $testIp, $now + $i);
}
$limiter->recordSuccess($resetEmail, $testIp);
runTest("Valid login after four failures clears the counter immediately",
    $limiter->attemptsInWindow($resetEmail, $testIp, $now + 5), 0);

// ---------------------------------------------------------
// SECTION 5: ADMIN ROLE ASSIGNMENT (shared test DB)
// ---------------------------------------------------------
echo "\n--- ADMIN ROLE ASSIGNMENT ---\n";

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$postModel = new Post($db);

$uniq = time() . '_' . rand(1000, 9999);
$password = "StrongPass123!";
$userModel->register("RBAC Student {$uniq}", "rbac_student_{$uniq}@nu.edu.pk", $password);
$userModel->register("RBAC Society {$uniq}", "rbac_society_{$uniq}@nu.edu.pk", $password);
$userModel->register("RBAC Admin {$uniq}", "rbac_admin_{$uniq}@nu.edu.pk", $password);

$student = $userModel->verifyPassword("rbac_student_{$uniq}@nu.edu.pk", $password);
$society = $userModel->verifyPassword("rbac_society_{$uniq}@nu.edu.pk", $password);
$admin = $userModel->verifyPassword("rbac_admin_{$uniq}@nu.edu.pk", $password);
$studentId = (int)$student['id'];
$societyId = (int)$society['id'];
$adminId = (int)$admin['id'];

$adminsBefore = $userModel->countSystemAdmins();
runTest("setRole promotes a student to society_admin",
    $userModel->setRole($societyId, 'society_admin'), true);
runTest("Promoted role reads back as society_admin",
    $authz->normalizeRole($userModel->getById($societyId)['role']), 'society_admin');
runTest("setRole rejects legacy 'admin'",
    $userModel->setRole($studentId, 'admin'), false);
runTest("setRole rejects unknown roles",
    $userModel->setRole($studentId, 'superuser'), false);
runTest("setRole rejects unknown users",
    $userModel->setRole(999999999, 'student'), false);
runTest("setRole promotes to system_admin",
    $userModel->setRole($adminId, 'system_admin'), true);
runTest("countSystemAdmins grows by one after promotion",
    $userModel->countSystemAdmins(), $adminsBefore + 1);
runTest("listUsers search finds the RBAC test user by name",
    count(array_filter(
        $userModel->listUsers("RBAC Student {$uniq}"),
        fn($u) => (int)$u['id'] === $studentId
    )), 1);
runTest("countUsers matches the search",
    $userModel->countUsers("rbac_student_{$uniq}@nu.edu.pk"), 1);
runTest("listUsers search with no match returns empty",
    $userModel->listUsers("no-such-user-{$uniq}"), []);

// ---------------------------------------------------------
// SECTION 6: AUTH->can() FOR LOGGED-IN USERS
// ---------------------------------------------------------
echo "\n--- AUTH->can() (live session) ---\n";

$authHelper = new Auth($authz, $csrfHelper);
$authHelper->login($student);
runTest("Student session cannot delete any post",
    $authHelper->can('post.delete_any'), false);
runTest("Student session can create posts",
    $authHelper->can('post.create'), true);
runTest("Student session cannot manage users",
    $authHelper->can('user.manage'), false);
// Re-fetch: $admin was loaded before the setRole promotion above,
// so its in-memory role is stale — the live DB row is system_admin.
$admin = $userModel->verifyPassword("rbac_admin_{$uniq}@nu.edu.pk", $password);
$authHelper->login($admin);
runTest("System admin session can delete any post",
    $authHelper->can('post.delete_any'), true);
runTest("System admin session can assign roles",
    $authHelper->can('role.assign'), true);
$authHelper->logout();

// ---------------------------------------------------------
// SECTION 7: SOCIETY ANNOUNCEMENTS
// ---------------------------------------------------------
echo "\n--- SOCIETY ANNOUNCEMENTS ---\n";

$studentController = new PostController($postModel, ['id' => $studentId, 'role' => 'student']);
$societyController = new PostController($postModel, ['id' => $societyId, 'role' => 'society_admin']);

$studentAnn = $studentController->handleCreate("Student tries announcement {$uniq}", true);
$studentPost = $studentAnn['post_id'] ? $postModel->getById((int)$studentAnn['post_id']) : false;
runTest("Student announcement request still creates the post",
    $studentAnn['success'], true);
runTest("Student announcement flag is downgraded to a normal post",
    (int)($studentPost['is_announcement'] ?? -1), 0);

$societyAnn = $societyController->handleCreate("Society announcement {$uniq}", true);
$societyPost = $societyAnn['post_id'] ? $postModel->getById((int)$societyAnn['post_id']) : false;
runTest("Society admin announcement request succeeds",
    $societyAnn['success'], true);
runTest("Society admin post is stored as an announcement",
    (int)($societyPost['is_announcement'] ?? -1), 1);

$normalPost = $societyController->handleCreate("Society normal post {$uniq}", false);
$normalRow = $normalPost['post_id'] ? $postModel->getById((int)$normalPost['post_id']) : false;
runTest("Society admin normal post is not flagged",
    (int)($normalRow['is_announcement'] ?? -1), 0);

$feedHasAnnouncement = false;
foreach ($postModel->getFeed(50, 0, 0, true) as $feedRow) {
    if ((int)$feedRow['id'] === (int)$societyAnn['post_id'] && !empty($feedRow['is_announcement'])) {
        $feedHasAnnouncement = true;
    }
}
runTest("getFeed exposes is_announcement for the announcement",
    $feedHasAnnouncement, true);

// ---------------------------------------------------------
// SECTION 8: SESSION REVOCATION ON ROLE CHANGE
// ---------------------------------------------------------
echo "\n--- SESSION REVOCATION ---\n";

$handler = new DbSessionHandler($db);
$db->exec("INSERT OR REPLACE INTO sessions (id, user_id, data, last_activity) VALUES "
    . "('rbac-test-session-1', {$studentId}, 'x', " . time() . "), "
    . "('rbac-test-session-2', {$studentId}, 'x', " . time() . ")");
$revoked = $handler->destroySessionsForUser($studentId, 'rbac-test-session-2');
runTest("destroySessionsForUser revokes all but the excepted session",
    $revoked, 1);
$remaining = (int)$db->query(
    "SELECT COUNT(*) FROM sessions WHERE user_id = {$studentId}"
)->fetchColumn();
runTest("Excepted session survives revocation", $remaining, 1);
$revokedAll = $handler->destroySessionsForUser($studentId);
runTest("destroySessionsForUser with no exception revokes the rest",
    $revokedAll, 1);
runTest("destroySessionsForUser rejects invalid users",
    $handler->destroySessionsForUser(0), false);

// ---------------------------------------------------------
// AUDIT C7: declared permissions are enforced
// ---------------------------------------------------------
echo "\n--- PERMISSION ENFORCEMENT (audit C7) ---\n";
require_once __DIR__ . '/../classes/Reply.php';
$c7Post = new Post($db);
$c7Reply = new Reply($db);
$c7Own = $c7Post->create($studentId, "C7 own post");
runTest("Post::delete refuses an own delete without post.delete_own",
    $c7Post->delete((int)$c7Own, $studentId, false, false), false);
runTest("Post::delete allows an own delete with post.delete_own",
    $c7Post->delete((int)$c7Own, $studentId, false, true), true);
$c7Target = $c7Post->create($studentId, "C7 reply target");
$c7ReplyId = $c7Reply->create($studentId, (int)$c7Target, "C7 reply");
runTest("Reply::delete refuses an own delete without reply.delete_own",
    $c7Reply->delete((int)$c7ReplyId, $studentId, false, false), false);
runTest("Reply::delete allows an own delete with reply.delete_own",
    $c7Reply->delete((int)$c7ReplyId, $studentId, false, true), true);
$c7SocietyCtl = new PostController(new Post($db), ['id' => $societyId, 'role' => 'society_admin', 'name' => 'S', 'email' => '']);
$c7Ann = $c7SocietyCtl->handleCreate("C7 announcement", true);
$c7StudentCtl = new PostController(new Post($db), ['id' => $studentId, 'role' => 'student', 'name' => 'St', 'email' => '']);
runTest("Student cannot delete someone else's announcement",
    $c7StudentCtl->handleDelete((int)$c7Ann['post_id'])['success'], false);
$c7AdminCtl = new PostController(new Post($db), ['id' => $adminId, 'role' => 'system_admin', 'name' => 'A', 'email' => '']);
runTest("System admin (post.delete_any + announcement.delete_any) can delete it",
    $c7AdminCtl->handleDelete((int)$c7Ann['post_id'])['success'], true);
runTest("handleDelete on a missing post fails cleanly",
    $c7AdminCtl->handleDelete(99999999)['success'], false);
$db->exec("DELETE FROM posts WHERE id = " . (int)$c7Target);

// ---------------------------------------------------------
// CLEANUP
// ---------------------------------------------------------
foreach ([$studentAnn['post_id'] ?? 0, $societyAnn['post_id'] ?? 0, $normalPost['post_id'] ?? 0] as $pid) {
    if ($pid) {
        $db->exec("DELETE FROM posts WHERE id = " . (int)$pid);
    }
}
$db->exec("DELETE FROM users WHERE id IN ({$studentId}, {$societyId}, {$adminId})");
$db->exec("DELETE FROM login_attempts WHERE email LIKE 'rbac\_%' ESCAPE '\\'");

// ---------------------------------------------------------
// SECTION 10: INJECTABLE HELPER OBJECTS (Intent 003 Unit 006 / EF-4a)
// ---------------------------------------------------------
echo "\n--- INJECTABLE HELPER OBJECTS (Intent 003 Unit 006) ---\n";
require_once __DIR__ . '/../classes/SafeRedirect.php';
require_once __DIR__ . '/../classes/SocialController.php';
require_once __DIR__ . '/../classes/AuthController.php';
require_once __DIR__ . '/../classes/ProfileController.php';
require_once __DIR__ . '/../classes/AdminController.php';

$injectedAuthz = new Authorization();
$injectedRedirect = new SafeRedirect();

runTest("Injected Authorization instance enforces permission matrix",
    $injectedAuthz->can(Authorization::ROLE_SYSTEM_ADMIN, 'user.manage')
    && !$injectedAuthz->can(Authorization::ROLE_STUDENT, 'user.manage'), true);
runTest("Injected SafeRedirect instance resolves safe local target",
    $injectedRedirect->target('explore.php?q=oop') === 'explore.php?q=oop'
    && $injectedRedirect->target('https://evil.example/') === 'index.php', true);

$helperClasses = [Auth::class, Csrf::class, Authorization::class, SafeRedirect::class];
$staticMethodCount = 0;
foreach ($helperClasses as $hCls) {
    $ref = new ReflectionClass($hCls);
    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        if ($m->isStatic()) {
            $staticMethodCount++;
        }
    }
}
runTest("Auth, Csrf, Authorization, and SafeRedirect have 0 public static methods",
    $staticMethodCount, 0);

// ---------------------------------------------------------
echo "\n=================================================\n";
echo "TEST SUMMARY: $passedTests / $totalTests Passed\n";
echo "=================================================\n";
