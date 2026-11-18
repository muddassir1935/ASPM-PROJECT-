---
unit: 009-regression-test-suite
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
---

# Unit Brief: Automated Regression Test Suite Expansion for All Fixes

## Assigned Owner
**Shahryar** (22K-4435) · Branch: `feature/fix-009-regression-tests`

## Purpose
Build dedicated automated regression test cases verifying each of the 8 bug fixes, ensuring zero regressions on both SQLite and MySQL and keeping the test suite green.

## Scope
### In Scope
- Add test assertions in `tests/`:
  - Verify `Bookmark::countByUser()` ignores soft-deleted posts.
  - Verify remember token revocation on password change and admin reset.
  - Verify moderation review triggers author and reporter notifications.
  - Verify login lockout triggers after 5 failed attempts.
  - Verify profile tab pagination parameters.
- Verify `php tests/run_all.php` runs with 100% pass rate.

### Out of Scope
- Adding load-testing infrastructure outside standard PHP CLI runner.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-9 | Comprehensive automated regression tests for all 8 bug fixes | Must |
