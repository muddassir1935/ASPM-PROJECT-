<?php
/**
 * Automated Unit Test Suite for the DB-backed session handler (Sprint 3).
 * Agile SPM - CampusConnect
 *
 * Run via CLI: php tests/SessionTest.php
 *
 * Note: this tests DbSessionHandler directly (read/write/destroy/gc),
 * not a live PHP session, so it runs cleanly under CLI without headers.
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/DbSessionHandler.php';

$db = Database::getInstance()->getConnection();
$handler = new DbSessionHandler($db);

// A real user is needed: sessions.user_id is a foreign key, so tagging
// a row with a non-existent user id must (and does) fail.
$userModel = new User($db);
$uniqUser = time() . '_' . rand(1000, 9999);
$userModel->register("Session Sam", "session_{$uniqUser}@nu.edu.pk", "StrongPass123!");
$sessionUser = $userModel->verifyPassword("session_{$uniqUser}@nu.edu.pk", "StrongPass123!");
$sessionUserId = (int)$sessionUser['id'];

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

$uniq = time() . '_' . rand(1000, 9999);
$sessionId = "test_session_{$uniq}";
$payload = "user_id|i:42;user_name|s:4:\"Test\";";

echo "--- DB SESSION HANDLER TESTS ---" . PHP_EOL;

assertTest("Read of unknown session returns empty string", $handler->read($sessionId) === '');
assertTest("Write stores session data", $handler->write($sessionId, $payload) === true);
assertTest("Read returns stored payload", $handler->read($sessionId) === $payload);
assertTest("Second write overwrites payload",
    $handler->write($sessionId, "updated|b:1;") === true && $handler->read($sessionId) === "updated|b:1;");

assertTest("setUserId tags the row",
    $handler->setUserId($sessionId, $sessionUserId) === true);
$rowStmt = $db->prepare("SELECT user_id, data FROM sessions WHERE id = :id");
$rowStmt->bindParam(':id', $sessionId, PDO::PARAM_STR);
$rowStmt->execute();
$row = $rowStmt->fetch(PDO::FETCH_ASSOC);
assertTest("user_id survives a later payload write",
    $row && (int)$row['user_id'] === $sessionUserId && $handler->write($sessionId, "again|b:1;") === true
    && ((int)$db->query("SELECT user_id FROM sessions WHERE id = '{$sessionId}'")->fetchColumn() === $sessionUserId));

assertTest("Destroy removes the row",
    $handler->destroy($sessionId) === true && $handler->read($sessionId) === '');
assertTest("Destroy of unknown session still succeeds", $handler->destroy($sessionId) === true);

$oldId = "old_session_{$uniq}";
$newId = "new_session_{$uniq}";
$handler->write($oldId, "old|b:1;");
$handler->write($newId, "new|b:1;");
// Age the old row past the GC threshold.
$db->prepare("UPDATE sessions SET last_activity = :t WHERE id = :id")
   ->execute([':t' => time() - 100000, ':id' => $oldId]);
assertTest("GC removes expired sessions only",
    $handler->gc(3600) >= 1 && $handler->read($oldId) === '' && $handler->read($newId) === "new|b:1;");
$handler->destroy($newId);

echo PHP_EOL . "Result: {$passed}/{$total} passed" . PHP_EOL;
exit($passed === $total ? 0 : 1);
