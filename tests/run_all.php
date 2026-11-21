<?php
/**
 * Run every automated suite in one go (Sprint 5):
 *   php tests/run_all.php
 * Exit code 0 when all suites pass, 1 otherwise. Each suite runs in
 * its own process against its own throwaway database (see
 * tests/bootstrap.php), so results are independent of run order.
 */
// CLI only (audit C5): under `php -S` without the router, or a misconfigured
// Apache, this file was reachable over HTTP and would run for any visitor.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$suites = [
    'UserTest.php',
    'PostTest.php',
    'SocialTest.php',
    'SocialControllerTest.php',
    'AuthProfileControllerTest.php',
    'NotificationTest.php',
    'SessionTest.php',
    'Sprint4Test.php',
    'AjaxFeedbackTest.php',
    'RbacTest.php',
    'ReportTest.php',
    'ProfilePaginationTest.php',
    'ProfileRepliesTest.php',
    'MysqlCompatTest.php',
    'ExploreRegressionTest.php',
];

$failedSuites = [];
foreach ($suites as $suite) {
    echo "\n===== {$suite} =====\n";
    $output = [];
    $code = 0;
    exec('php ' . escapeshellarg(__DIR__ . '/' . $suite) . ' 2>&1', $output, $code);
    echo implode("\n", $output) . "\n";
    // The suites report failures as "[FAIL]" lines; a non-zero exit
    // code means the script itself crashed.
    $text = implode("\n", $output);
    if ($code !== 0 || strpos($text, '[FAIL]') !== false) {
        $failedSuites[] = $suite;
    }
}

if ($failedSuites === []) {
    echo "\nALL SUITES PASSED\n";
    exit(0);
}
echo "\nFAILED SUITES: " . implode(', ', $failedSuites) . "\n";
exit(1);
