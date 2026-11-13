<?php
/**
 * Benchmark Script (Sprint 4, WBS 4.3): Database vs Cache Latency Comparison.
 *
 * Runs N iterations of feed fetching:
 * 1. Direct Database Query (no cache)
 * 2. Cached Query (via Cache class - Redis or Memory Fallback)
 *
 * Run via CLI: php benchmark.php
 * Or visit via web server in browser — the web view also renders a small
 * SVG bar-chart dashboard of the two averages (WBS 4.4).
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
$eol = $isCli ? PHP_EOL : "<br>";

if (!$isCli) {
    echo '<pre style="white-space: pre-wrap; overflow-wrap: break-word; max-width: 100%;">';
}

echo "==========================================================" . $eol;
echo "  CampusConnect - Cache Performance Benchmark (Sprint 4)  " . $eol;
echo "==========================================================" . $eol . $eol;

$db = Database::getInstance()->getConnection();
$postModel = new Post($db);
$cache = Cache::getInstance();

echo "Database Driver: " . Database::getInstance()->getDriver() . $eol;
echo "Cache Driver:    " . $cache->getDriverName() . ($cache->isAvailable() ? " (Active)" : " (Fallback)") . $eol;
echo "Test Parameters: 50 iterations, timeline feed query (limit 20)" . $eol . $eol;

$iterations = 50;

// 1. Direct Database Queries (bypassCache: the feed is cached since the
// Sprint 4 fixes, and this arm must keep measuring the database itself)
$startDb = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $feed = $postModel->getFeed(20, 0, 0, true);
}
$dbTime = (microtime(true) - $startDb) * 1000; // in milliseconds
$dbAvg = $dbTime / $iterations;

echo sprintf("[-] Direct DB Fetch:   Total: %8.2f ms | Avg: %6.3f ms/query", $dbTime, $dbAvg) . $eol;

// 2. Cached Queries
// Seed the cache first
$cache->set('benchmark_feed_sample', $feed, 300);

$startCache = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $cachedFeed = $cache->get('benchmark_feed_sample');
}
$cacheTime = (microtime(true) - $startCache) * 1000;
$cacheAvg = $cacheTime / $iterations;

echo sprintf("[+] Cached Read:       Total: %8.2f ms | Avg: %6.3f ms/query", $cacheTime, $cacheAvg) . $eol . $eol;

$speedup = $cacheAvg > 0 ? ($dbAvg / $cacheAvg) : 1;
echo sprintf(">>> Performance Gain: Cache is %.1fx faster than direct DB queries! <<<", $speedup) . $eol . $eol;

// Cleanup
$cache->delete('benchmark_feed_sample');

if (!$isCli) {
    echo "</pre>";

    // --- Minimal SVG dashboard (WBS 4.4): average latency per arm ---
    $maxAvg = max($dbAvg, $cacheAvg, 0.0001);
    $barW = function (float $avg) use ($maxAvg): int {
        return max(2, (int)round(520 * ($avg / $maxAvg)));
    };
    $dbDriverLabel = htmlspecialchars(Database::getInstance()->getDriver());
    $cacheDriverLabel = htmlspecialchars($cache->getDriverName() . ' / ' . $cache->getBackendName());
    echo '<section style="font-family: sans-serif; max-width: 640px; overflow-wrap: break-word;">';
    echo '<h2 style="font-size: 1.1rem;">Benchmark dashboard</h2>';
    echo '<svg width="100%" height="120" viewBox="0 0 640 120" style="max-width: 640px; height: auto;" role="img" aria-label="Average latency bar chart">';
    echo '<text x="0" y="24" font-size="13">Direct DB (' . $dbDriverLabel . ')</text>';
    echo '<rect x="150" y="10" width="' . $barW($dbAvg) . '" height="20" fill="#B42318"></rect>';
    echo '<text x="' . (158 + $barW($dbAvg)) . '" y="25" font-size="13">' . sprintf('%.3f ms', $dbAvg) . '</text>';
    echo '<text x="0" y="64" font-size="13">Cached (' . $cacheDriverLabel . ')</text>';
    echo '<rect x="150" y="50" width="' . $barW($cacheAvg) . '" height="20" fill="#027A48"></rect>';
    echo '<text x="' . (158 + $barW($cacheAvg)) . '" y="65" font-size="13">' . sprintf('%.3f ms', $cacheAvg) . '</text>';
    echo '<text x="0" y="104" font-size="13" font-weight="bold">Speedup: ' . sprintf('%.1fx', $speedup) . '</text>';
    echo '</svg>';
    echo '<p style="font-size: 0.85rem; color: #555;">Averages over ' . $iterations . ' iterations of the timeline feed query (limit 20). '
        . 'With no Redis server the cached arm reads the in-process memory fallback; '
        . 'run on the demo machine with Redis up for the MySQL-vs-Redis figures.</p>';
    echo '</section>';
}
