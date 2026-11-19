<?php
/** Regression checks for profile likes pagination. */

$source = file_get_contents(__DIR__ . '/../profile.php');
$passed = 0;
$failed = 0;

function assertProfilePagination(string $name, bool $condition): void
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

assertProfilePagination(
    'Profile Likes uses 20-item pages for count, query, and pager',
    str_contains($source, '$likesPerPage = 20;')
        && str_contains($source, 'clampPage((int)($_GET[\'page\'] ?? 1), $likesTotal, $likesPerPage)')
        && str_contains($source, 'getLikedPosts((int)$profile[\'id\'], $likesPerPage, ($likesPage - 1) * $likesPerPage')
        && str_contains($source, '$likesPage * $likesPerPage < $likesTotal')
);
assertProfilePagination(
    'Profile Likes pager preserves its active tab',
    str_contains($source, "'profile.php?id=' . (int)\$profile['id'] . '&tab=likes'")
);

echo "\nProfilePaginationTest: {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
