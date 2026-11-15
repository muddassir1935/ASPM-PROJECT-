<?php
/**
 * MySQL compatibility suite (audit fix C1).
 *
 * Every other suite runs on the throwaway SQLite database, so SQL that
 * SQLite accepts but MySQL rejects slipped through — Explore search and
 * the admin user search crashed on MySQL with error 1064 for every query
 * (LIKE ... ESCAPE '\' is an unterminated string in MySQL).
 *
 * This suite creates its OWN temporary MySQL database, imports
 * db/schema.sql into it, exercises the queries the web app runs, and
 * drops the database at the end. The live `campusconnect` database is
 * never touched. When MySQL is unreachable the suite prints SKIPPED and
 * exits 0, so `php tests/run_all.php` still works without XAMPP.
 *
 * Connection settings: CC_MYSQL_HOST / CC_MYSQL_USER / CC_MYSQL_PASS
 * (defaults match config/Database.php: localhost / root / empty).
 *
 * Run via CLI: php tests/MysqlCompatTest.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Post.php';

$host = getenv('CC_MYSQL_HOST') ?: 'localhost';
$user = getenv('CC_MYSQL_USER') ?: 'root';
$pass = getenv('CC_MYSQL_PASS') ?: '';
$dbName = 'campusconnect_test_' . getmypid();

try {
    $server = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2,
    ]);
} catch (PDOException $e) {
    echo "MySQL unreachable ({$e->getMessage()}) — MysqlCompatTest SKIPPED." . PHP_EOL;
    exit(0);
}

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

/** Run a test body, turning any exception into a failed assertion. */
function attempt(string $testName, callable $body) {
    try {
        $body();
    } catch (Throwable $e) {
        assertTest($testName, false, get_class($e) . ': ' . $e->getMessage());
    }
}

/** Split schema.sql into statements, skipping its own CREATE DATABASE / USE lines. */
function schemaStatements(string $sql): array {
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $out = [];
    foreach (explode(';', $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '' || preg_match('/^(CREATE DATABASE|USE)\b/i', $stmt)) {
            continue;
        }
        $out[] = $stmt;
    }
    return $out;
}

$server->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
register_shutdown_function(static function () use ($server, $dbName): void {
    $server->exec("DROP DATABASE IF EXISTS `{$dbName}`");
});

$db = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "=================================================" . PHP_EOL;
echo "   MySQL Compatibility Test Suite (temp DB {$dbName})" . PHP_EOL;
echo "=================================================" . PHP_EOL;

// --- Schema imports cleanly ---
$schemaSql = file_get_contents(__DIR__ . '/../db/schema.sql');
attempt('schema.sql imports into an empty MySQL database', function () use ($db, $schemaSql) {
    foreach (schemaStatements($schemaSql) as $stmt) {
        $db->exec($stmt);
    }
    assertTest('schema.sql imports into an empty MySQL database', true);
});

// --- C6: schema.sql is idempotent and utf8mb4 ---
attempt('schema.sql can be imported a second time (idempotent)', function () use ($db, $schemaSql) {
    foreach (schemaStatements($schemaSql) as $stmt) {
        $db->exec($stmt);
    }
    assertTest('schema.sql can be imported a second time (idempotent)', true);
});
attempt('Every table uses a utf8mb4 collation', function () use ($db, $dbName) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables
                          WHERE table_schema = :db AND table_collation NOT LIKE 'utf8mb4%'");
    $stmt->execute([':db' => $dbName]);
    assertTest('Every table uses a utf8mb4 collation', (int)$stmt->fetchColumn() === 0);
});

// --- Seed users/posts through the real models ---
$userModel = new User($db);
$postModel = new Post($db);
$userModel->register('Ali Search', 'ali.search@nu.edu.pk', 'StrongPass123!');
$userModel->register('Percent_Name', 'pct@nu.edu.pk', 'StrongPass123!');
$aliId = (int)$db->query("SELECT id FROM users WHERE email = 'ali.search@nu.edu.pk'")->fetchColumn();
$postModel->create($aliId, 'Hackathon tonight! 100% fun at the lab');
$postModel->create($aliId, 'Plain post with no specials');

// --- C1: LIKE searches must run on MySQL ---
attempt('Post::searchPosts runs on MySQL', function () use ($postModel) {
    $rows = $postModel->searchPosts('hackathon');
    assertTest('Post::searchPosts runs on MySQL', count($rows) === 1, 'got ' . count($rows));
});
attempt('Post::countSearch runs on MySQL', function () use ($postModel) {
    assertTest('Post::countSearch runs on MySQL', $postModel->countSearch('hackathon') === 1);
});
attempt('A literal % in a search matches literally', function () use ($postModel) {
    assertTest('A literal % in a search matches literally', $postModel->countSearch('%') === 1);
});
attempt('A literal _ in a search matches literally', function () use ($postModel) {
    assertTest('A literal _ in a search matches literally', $postModel->countSearch('_') === 0);
});
attempt('The escape character itself (!) matches literally', function () use ($postModel) {
    assertTest('The escape character itself (!) matches literally', $postModel->countSearch('!') === 1);
});
attempt('User::searchUsers runs on MySQL', function () use ($userModel) {
    $rows = $userModel->searchUsers('ali');
    assertTest('User::searchUsers runs on MySQL', count($rows) === 1, 'got ' . count($rows));
});
attempt('User::searchUsers treats _ literally', function () use ($userModel) {
    $rows = $userModel->searchUsers('t_N');
    assertTest('User::searchUsers treats _ literally', count($rows) === 1 && $rows[0]['name'] === 'Percent_Name');
});
attempt('User::listUsers search runs on MySQL', function () use ($userModel) {
    $rows = $userModel->listUsers('search');
    assertTest('User::listUsers search runs on MySQL', count($rows) === 1);
});
attempt('User::countUsers search runs on MySQL', function () use ($userModel) {
    assertTest('User::countUsers search runs on MySQL', $userModel->countUsers('nu.edu.pk') === 2);
});

attempt('Notification visibility filter runs on MySQL', function () use ($db, $aliId) {
    require_once __DIR__ . '/../classes/Notification.php';
    $n = new Notification($db);
    $n->create($aliId, 'follow', 1, 'x followed you', null);
    assertTest('Notification visibility filter runs on MySQL', $n->countUnread($aliId) === 1 && count($n->listAll($aliId)) === 1 && $n->countAll($aliId) === 1);
});

attempt('Hashtag::getTrending (windowed) runs on MySQL', function () use ($db, $postModel, $aliId) {
    require_once __DIR__ . '/../classes/Hashtag.php';
    $postModel->create($aliId, 'Trend check #mysqltrend');
    $tags = array_column((new Hashtag($db))->getTrending(10), 'tag');
    assertTest('Hashtag::getTrending (windowed) runs on MySQL', in_array('mysqltrend', $tags, true));
});

echo PHP_EOL . "=================================================" . PHP_EOL;
echo "TEST SUMMARY: {$passed} / {$total} Passed" . PHP_EOL;
echo "=================================================" . PHP_EOL;
exit($passed === $total ? 0 : 1);
