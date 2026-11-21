<?php
/**
 * Automated Test Suite for SQLite Schema Generator (intent 003, unit 010 / EF-7)
 * CampusConnect - Agile Software Project Management
 *
 * Verifies that scripts/generate_sqlite_schema.php accurately translates
 * db/schema.sql into SQLite DDL, covering all 12 tables and constraints.
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../scripts/generate_sqlite_schema.php';

$passed = 0;
$total = 0;

function assertGenTest(string $testName, bool $condition, ?string $detail = null): void {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo " [PASS] Test {$total}: {$testName}\n";
    } else {
        echo " [FAIL] Test {$total}: {$testName}" . ($detail ? " ({$detail})" : "") . "\n";
    }
}

echo "=== SQLITE SCHEMA GENERATOR TEST SUITE (UNIT 010 / EF-7) ===\n\n";

// 1. Generation from db/schema.sql succeeds
$ddl = generateSqliteSchema();
assertGenTest("generateSqliteSchema() produces non-empty output", !empty($ddl) && strlen($ddl) > 500);

// 2. All 12 canonical tables are generated
$expectedTables = [
    'users', 'posts', 'follows', 'replies', 'bookmarks', 'sessions',
    'likes', 'hashtags', 'post_hashtags', 'notifications', 'login_attempts', 'reports'
];

foreach ($expectedTables as $tbl) {
    assertGenTest("Generated schema defines table '{$tbl}'",
        (bool)preg_match("/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+{$tbl}\b/i", $ddl));
}

// 3. MySQL-isms are thoroughly translated
assertGenTest("AUTO_INCREMENT translated to AUTOINCREMENT",
    !str_contains($ddl, 'AUTO_INCREMENT') && str_contains($ddl, 'AUTOINCREMENT'));
assertGenTest("ENGINE=InnoDB stripped", !str_contains($ddl, 'ENGINE='));
assertGenTest("DEFAULT CHARSET stripped", !str_contains($ddl, 'CHARSET=') && !str_contains($ddl, 'CHARACTER SET'));
assertGenTest("COLLATE stripped", !str_contains($ddl, 'COLLATE'));
assertGenTest("TIMESTAMP converted to DATETIME", !preg_match('/\bTIMESTAMP\b/i', $ddl));

// 4. Unit 008 (EF-5) constraints are generated
assertGenTest("reports.reason has CHECK constraint for valid reasons",
    str_contains($ddl, "CHECK (reason IN ('spam','harassment','misinformation','inappropriate','violence','other'))"));
assertGenTest("reports.status has CHECK constraint for valid statuses",
    str_contains($ddl, "CHECK (status IN ('pending','dismissed','actioned'))"));

// 5. Unit 009 (EF-6) constraint is generated
assertGenTest("notifications has FOREIGN KEY on reference_id to posts(id) ON DELETE CASCADE",
    str_contains($ddl, "FOREIGN KEY (reference_id) REFERENCES posts(id) ON DELETE CASCADE"));

// 6. Schema drift fix: posts.is_announcement is included
assertGenTest("posts table includes is_announcement column",
    str_contains($ddl, "is_announcement INTEGER NOT NULL DEFAULT 0"));

// 7. Standalone indexes are generated
$expectedIndexes = [
    'idx_posts_user_id', 'idx_posts_created_at', 'idx_follows_followed_id',
    'idx_replies_post_id', 'idx_replies_created_at', 'idx_bookmarks_post_id',
    'idx_sessions_user_id', 'idx_sessions_last_activity', 'idx_likes_post_id',
    'idx_post_hashtags_tag', 'idx_notifications_user_read', 'idx_notifications_user_created',
    'idx_reports_status', 'idx_reports_post'
];

foreach ($expectedIndexes as $idx) {
    assertGenTest("Index '{$idx}' is generated as standalone statement",
        str_contains($ddl, "CREATE INDEX IF NOT EXISTS {$idx}"));
}

// 8. In-memory execution: SQLite parses and executes the generated DDL cleanly
$memPdo = new PDO('sqlite::memory:');
$memPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$memPdo->exec("PRAGMA foreign_keys = ON;");

$execSuccess = false;
try {
    $memPdo->exec($ddl);
    $execSuccess = true;
} catch (PDOException $e) {
    echo "SQL error: " . $e->getMessage() . "\n";
}
assertGenTest("Fresh SQLite in-memory database executes generated schema with zero errors", $execSuccess === true);

// 9. Verified enforcement of generated constraints in SQLite
// 9a. reports reason constraint
$badReasonBlocked = false;
try {
    $memPdo->prepare("INSERT INTO users (id, name, email, password_hash) VALUES (1, 'U1', 'u1@test.com', 'h')")->execute();
    $memPdo->prepare("INSERT INTO posts (id, user_id, content) VALUES (1, 1, 'P1')")->execute();
    $memPdo->prepare("INSERT INTO reports (reporter_id, post_id, reason) VALUES (1, 1, 'invalid_reason')")->execute();
} catch (PDOException $e) {
    $badReasonBlocked = true;
}
assertGenTest("Generated SQLite schema enforces reports.reason CHECK constraint", $badReasonBlocked === true);

// 9b. notifications reference_id FK constraint
$badFkBlocked = false;
try {
    $memPdo->prepare("INSERT INTO notifications (user_id, actor_id, type, reference_id, message) VALUES (1, 1, 'like', 9999, 'msg')")->execute();
} catch (PDOException $e) {
    $badFkBlocked = true;
}
assertGenTest("Generated SQLite schema enforces notifications.reference_id FK constraint", $badFkBlocked === true);

// 9c. notifications cascade deletion
$memPdo->prepare("INSERT INTO notifications (user_id, actor_id, type, reference_id, message) VALUES (1, 1, 'like', 1, 'valid like')")->execute();
$notifCountBefore = (int)$memPdo->query("SELECT COUNT(*) FROM notifications WHERE reference_id = 1")->fetchColumn();
$memPdo->exec("DELETE FROM posts WHERE id = 1");
$notifCountAfter = (int)$memPdo->query("SELECT COUNT(*) FROM notifications WHERE reference_id = 1")->fetchColumn();
assertGenTest("Generated SQLite schema cascades notification deletion when post is deleted",
    $notifCountBefore === 1 && $notifCountAfter === 0);

echo "\n=================================================\n";
echo "SCHEMA GENERATOR TEST SUMMARY: {$passed} / {$total} Passed\n";
echo "=================================================\n";

if ($passed !== $total) {
    exit(1);
}
