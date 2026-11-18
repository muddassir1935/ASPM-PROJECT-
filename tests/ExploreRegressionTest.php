<?php
/** Regression checks for Explore search result paging. */

$source = file_get_contents(__DIR__ . '/../explore.php');
$passed = 0;
$failed = 0;

function assertExploreRegression(string $name, bool $condition): void
{
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] {$name}\n";
        $passed++;
    } else {
        echo "[FAIL] {$name}\n";
        $failed++;
    }
}

assertExploreRegression(
    'User search query runs only on page one',
    preg_match('/if\s*\(\$page\s*===\s*1\)\s*\{\s*\$foundUsers\s*=\s*\$userModel->searchUsers/s', $source) === 1
);
assertExploreRegression(
    'User cards render only on page one',
    preg_match('/if\s*\(\$page\s*===\s*1\)\s*:\s*<h4 class="section-title">\s*People matching.*?foreach\s*\(\$foundUsers\s+as\s+\$foundUser\)/s', $source) === 1
);

echo "\nExploreRegressionTest: {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
