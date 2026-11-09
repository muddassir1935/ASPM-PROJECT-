<?php
/**
 * Benchmark Script (Sprint 4, WBS 4.3): Database vs Cache Latency Comparison.
 *
 * Runs N iterations of feed fetching:
 * 1. Direct Database Query (no cache)
 * 2. Cached Query (via Cache class - Redis or Memory Fallback)
 *
 * Run via CLI: php benchmark.php
 * Or visit via web server in browser.
 */
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Post.php';
require_once __DIR__ . '/classes/Cache.php';

$isCli = (php_sapi_name() === 'cli');
$eol = $isCli ? PHP_EOL : "<br>";

if (!$isCli) {
    echo "<pre>";
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

// 1. Direct Database Queries
$startDb = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $feed = $postModel->getFeed(20, 0);
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
}
