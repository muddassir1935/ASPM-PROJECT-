<?php
/** Regression checks for the shared AJAX feedback and notification badge UI. */

$footer = file_get_contents(__DIR__ . '/../includes/footer.php');
$notifications = file_get_contents(__DIR__ . '/../notifications.php');
$passed = 0;
$failed = 0;

function assertAjaxFeedbackTest(string $name, bool $condition): void
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

assertAjaxFeedbackTest(
    '401 and 403 AJAX responses show the session-expiry toast',
    str_contains($footer, 'response.status === 401 || response.status === 403')
        && str_contains($footer, 'Your session has expired. Please refresh and log in again.')
        && str_contains($footer, '.catch(ccFail)')
);
assertAjaxFeedbackTest(
    'Mark all as read clears navigation notification badges without reload',
    str_contains($notifications, 'updateUnreadBadges(0)')
        && str_contains($notifications, "navLink.setAttribute('aria-label'")
        && str_contains($notifications, "badge.style.display = 'none'")
);

echo "\nAjaxFeedbackTest: {$passed} passed, {$failed} failed\n";
if ($failed > 0) {
    exit(1);
}
