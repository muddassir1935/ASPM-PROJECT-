---
unit: 009-regression-test-suite
intent: 002-quality-enhancements
phase: construction
status: completed
created: 2026-11-18T09:10:00Z
updated: 2026-11-21T20:47:00Z
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

## Verification (22 Nov 2026 — verified by Muddassir & Muse, unit closed as covered)

Every fix in intent 002 shipped with its own tests, so the dedicated regression suite this unit planned is already in place — check by check:

| Named check | Where it lives | Verdict |
|---|---|---|
| `Bookmark::countByUser()` ignores soft-deleted posts | `tests/SocialTest.php` (count drops to 0 after soft-delete; invalid-ID guard) | Covered |
| Remember token revocation on password change / admin reset | — | N/A — no remember-me feature exists (see unit 004 finding); session revocation on credential change is covered by `AuthProfileControllerTest` (password change revokes other sessions) and `AdminControllerTest` (reset revokes the target's sessions) |
| Moderation review triggers author and reporter notifications | `tests/ReportTest.php` ("Dismissed report notifies its reporter", "Actioned post notifies its author") | Covered |
| Login lockout triggers after 5 failed attempts | `tests/RbacTest.php` ("5th failure locks the pair", "Lock duration is five minutes") | Covered |
| Profile tab pagination parameters | `tests/ProfilePaginationTest.php` (2 checks) + `tests/ProfileRepliesTest.php` (16 checks) | Covered |
| `php tests/run_all.php` 100% pass | 601/601 SQLite locally; MySQL suite green in CI (runs #21, #22, #24, #27) | Covered |
| Suite count 412 → 430+ | 601 tests at close-out | Covered |

No new test file was added: the coverage already exists, and a duplicate suite would only re-assert the same behaviour.
