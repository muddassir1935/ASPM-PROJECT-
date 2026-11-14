<?php
/**
 * Benchmark Script (Sprint 4, WBS 4.3): Database vs Cache Latency Comparison.
 *
 * Runs N iterations of feed fetching:
 * 1. Direct Database Query (no cache)
 * 2. Cached Query (via Cache class - Redis or Memory Fallback)
 *
 * Run via CLI: php benchmark.php
 * Or visit via web server in browser — the web view renders the same
 * numbers as a readable page in the app shell (Sprint 5 UI polish,
 * M-11): a results table plus a fixed bar chart. The CLI output is
 * unchanged plain text.
 *
 * Web access is restricted to system administrators (see the gate below):
 * each visit runs 50 feed queries, so it must not be public. CLI runs
 * are unaffected.
 *
 * Note on method: the "Direct DB" arm bypasses the feed cache
 * (getFeed(..., bypassCache: true)) so it always measures real queries,
 * and the "Cached" arm measures full cache reads of the same payload.
 * For the headline MySQL-vs-Redis comparison, run this on a machine with
 * MySQL + a Redis server (phpredis or the built-in TinyRedisClient).
 */
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Post.php';
require_once __DIR__ . '/classes/Cache.php';

$isCli = (php_sapi_name() === 'cli');

// Sprint 5 final fix (audit item 9): the polished web view used to be both
// public (on php -S, no gate at all) and dead (on Apache, .htaccess 403'd
// it). Now .htaccess leaves this file alone and the gate below decides:
// logged-out visitors go to login, non-admins get 403, system admins see
// the page. The gate runs BEFORE the 50-query benchmark loops.
if (!$isCli) {
    require_once __DIR__ . '/classes/Auth.php';
    require_once __DIR__ . '/classes/Authorization.php';
    $benchUser = Auth::user();
    if (!$benchUser) {
        header('Location: login.php');
        exit;
    }
    if (($benchUser['role'] ?? '') !== 'system_admin') {
        http_response_code(403);
        exit('Benchmark is available to system administrators only.');
    }
}

$db = Database::getInstance()->getConnection();
$postModel = new Post($db);
$cache = Cache::getInstance();

$dbDriver = Database::getInstance()->getDriver();
$cacheDriver = $cache->getDriverName();
$cacheBackend = $cache->getBackendName();
$cacheStatus = $cache->isAvailable() ? 'Active' : 'Fallback';

$iterations = 50;

// 1. Direct Database Queries (bypassCache: the feed is cached since the
// Sprint 4 fixes, and this arm must keep measuring the database itself)
$startDb = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $feed = $postModel->getFeed(20, 0, 0, true);
}
$dbTime = (microtime(true) - $startDb) * 1000; // in milliseconds
$dbAvg = $dbTime / $iterations;

// 2. Cached Queries
// Seed the cache first
$cache->set('benchmark_feed_sample', $feed, 300);

$startCache = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $cachedFeed = $cache->get('benchmark_feed_sample');
}
$cacheTime = (microtime(true) - $startCache) * 1000;
$cacheAvg = $cacheTime / $iterations;

$speedup = $cacheAvg > 0 ? ($dbAvg / $cacheAvg) : 1;

// Cleanup
$cache->delete('benchmark_feed_sample');

if ($isCli) {
    $eol = PHP_EOL;
    echo "==========================================================" . $eol;
    echo "  CampusConnect - Cache Performance Benchmark (Sprint 4)  " . $eol;
    echo "==========================================================" . $eol . $eol;

    echo "Database Driver: " . $dbDriver . $eol;
    echo "Cache Driver:    " . $cacheDriver . " (" . $cacheStatus . ")" . $eol;
    echo "Test Parameters: 50 iterations, timeline feed query (limit 20)" . $eol . $eol;

    echo sprintf("[-] Direct DB Fetch:   Total: %8.2f ms | Avg: %6.3f ms/query", $dbTime, $dbAvg) . $eol;
    echo sprintf("[+] Cached Read:       Total: %8.2f ms | Avg: %6.3f ms/query", $cacheTime, $cacheAvg) . $eol . $eol;

    echo sprintf(">>> Performance Gain: Cache is %.1fx faster than direct DB queries! <<<", $speedup) . $eol . $eol;
    exit;
}

// --- Web view: readable page in the app shell (M-11) ---
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/social_widgets.php';

$maxAvg = max($dbAvg, $cacheAvg, 0.0001);
$barW = function (float $avg) use ($maxAvg): int {
    return max(2, (int)round(390 * ($avg / $maxAvg)));
};
$dbBarW = $barW($dbAvg);
$cacheBarW = $barW($cacheAvg);
$dbLabel = 'Direct DB (' . $dbDriver . ')';
$cacheLabel = 'Cached (' . $cacheDriver . ')';
?>

<div class="layout-3col">
    <?php renderLeftNav('admin'); ?>

    <div class="col-main">
        <div class="col-head admin-header-flex">
            <span>Performance Benchmark</span>
            <a href="admin.php" class="admin-bench-pill" title="Return to Admin Panel">
                &larr; Back to Admin
            </a>
        </div>

        <div class="bench-card">
            <p class="bench-meta">
                <strong>Database:</strong> <?= htmlspecialchars($dbDriver) ?> &middot;
                <strong>Cache:</strong> <?= htmlspecialchars($cacheDriver . ' / ' . $cacheBackend) ?> (<?= htmlspecialchars($cacheStatus) ?>) &middot;
                <strong>Test:</strong> <?= $iterations ?> iterations, timeline feed query (limit 20)
            </p>

            <table class="bench-table">
                <thead>
                    <tr><th>Arm</th><th>Total</th><th>Average per query</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Direct DB</td>
                        <td><?= sprintf('%.2f ms', $dbTime) ?></td>
                        <td><?= sprintf('%.3f ms', $dbAvg) ?></td>
                    </tr>
                    <tr>
                        <td>Cached read</td>
                        <td><?= sprintf('%.2f ms', $cacheTime) ?></td>
                        <td><?= sprintf('%.3f ms', $cacheAvg) ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="bench-callout">
                Cache is <?= sprintf('%.1f', $speedup) ?>&times; faster than direct DB queries.
            </div>
        </div>

        <div class="bench-chart">
            <h4 class="section-title" style="padding-left: 0;">Average latency per query</h4>
            <?php
            // Chart geometry (M-11 fix): labels live in a fixed 170px
            // column, bars run 170–560, and each value sits just past its
            // own bar end — the old chart drew the Direct DB value past
            // the 640px viewBox (invisible) and the Cached label across
            // its own value.
            ?>
            <svg width="100%" height="132" viewBox="0 0 640 132" style="max-width: 640px; height: auto; display: block; color: var(--ink);" role="img" aria-label="Average latency bar chart: Direct DB <?= sprintf('%.3f ms', $dbAvg) ?>, Cached <?= sprintf('%.3f ms', $cacheAvg) ?>">
                <text x="0" y="30" font-size="13" font-weight="600" fill="currentColor"><?= htmlspecialchars($dbLabel) ?></text>
                <rect x="170" y="14" width="<?= $dbBarW ?>" height="22" fill="#B42318" rx="3"></rect>
                <text x="<?= 178 + $dbBarW ?>" y="30" font-size="13" fill="currentColor"><?= sprintf('%.3f ms', $dbAvg) ?></text>
                <text x="0" y="80" font-size="13" font-weight="600" fill="currentColor"><?= htmlspecialchars($cacheLabel) ?></text>
                <rect x="170" y="64" width="<?= $cacheBarW ?>" height="22" fill="#027A48" rx="3"></rect>
                <text x="<?= 178 + $cacheBarW ?>" y="80" font-size="13" fill="currentColor"><?= sprintf('%.3f ms', $cacheAvg) ?></text>
                <text x="0" y="120" font-size="13" font-weight="700" fill="currentColor">Speedup: <?= sprintf('%.1f', $speedup) ?>&times;</text>
            </svg>
            <p class="bench-note" style="padding-left: 0;">
                Averages over <?= $iterations ?> iterations of the timeline feed query (limit 20).
                With no Redis server the cached arm reads the in-process memory fallback;
                run on the demo machine with Redis up for the MySQL-vs-Redis figures.
            </p>
        </div>
    </div>

    <div class="col-side">
        <?php renderTrendingHashtagsWidget($db); ?>
        <?php if ($currentUser): ?>
            <?php renderWhoToFollowWidget($db, (int)$currentUser['id']); ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
