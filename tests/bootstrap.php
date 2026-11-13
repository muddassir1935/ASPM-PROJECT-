<?php
/**
 * Test bootstrap (Sprint 5, H4): point every suite at an isolated
 * throwaway SQLite database.
 *
 * The suites used to run against the live demo database and never
 * cleaned up — the local DB accumulated 200+ junk users, which showed
 * up in Explore / Who-to-Follow during demos and also made SocialTest
 * Test 16 flaky (Sam fell out of the top-10 suggestions). Each test
 * process now gets a fresh temp file, deleted on shutdown.
 *
 * Usage: require this file BEFORE config/Database.php in every test.
 * (tests/smoke_db.php is the exception: the live smoke script needs
 * the REAL database the web server sees, and cleans up after itself.)
 */
$testDbPath = sys_get_temp_dir() . '/campusconnect_test_' . getmypid() . '.sqlite';
if (file_exists($testDbPath)) {
    unlink($testDbPath);
}
putenv('CAMPUSCONNECT_TEST_DB=' . $testDbPath);
register_shutdown_function(static function () use ($testDbPath): void {
    if (file_exists($testDbPath)) {
        unlink($testDbPath);
    }
});
