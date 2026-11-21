# CampusConnect — Team Task Allocation & AI-DLC Bug Fixes

**Intent:** `002-quality-enhancements` (Scope: 100% Bug Fixes & Hardening of Existing Features · Zero New Features)  
**Date:** 18 Nov 2026  
**Status:** In main since 17 Nov 2026. Units 001–003, 005–008 merged; unit 004 closed as not-applicable after investigation (22 Nov — no remember-me feature exists, see its brief); unit 009 covered by the existing suite (22 Nov — 601 tests, see its brief).

---

## Team Roster & Assigned Bug Fixes

| # | Team Member | Roll Number | Assigned Unit | Dedicated Git Branch | Targeted Existing Bug / Improvement |
|---|-------------|-------------|---------------|----------------------|--------------------------------------|
| 1 | **Muddassir Rizwan** | 23K-0784 | `001-aidlc-governance` | `feature/fix-001-aidlc-governance` | AI-DLC lifecycle governance, memory bank sync, PR review gates, ensuring zero new feature bloat |
| 2 | **Faiz Hussain** | 23K-2061 | `002-bookmark-counter-fix` | `feature/fix-002-bookmark-counter` | Fix `Bookmark::countByUser()` counting soft-deleted posts, fixing counter mismatch ("Your Bookmarks (5)" showing only 3) |
| 3 | **Qaim Mehdi** | 23K-2075 | `003-moderation-feedback-pagination` | `feature/fix-003-moderation-feedback` | Notify authors when posts are removed by admin, notify reporters upon review, and add pagination to `admin.php#reports` for >50 items |
| 4 | **Haseeb Asif** | 23K-0539 | `004-auth-token-invalidation` | `feature/fix-004-auth-token-invalidation` | Invalidate persistent `remember_tokens` on password change & admin reset (currently only session rows are destroyed) |
| 5 | **Muhammad Hadi** | 23K-0755 | `005-explore-search-casing-fix` | `feature/fix-005-explore-search-casing` | Fix user search repetition on Explore page 2+ (`?q=...&page=2`), and handle mixed-casing hashtag lookups across SQL collations |
| 6 | **Yuneeb** | 23K-0733 | `006-profile-tab-pagination` | `feature/fix-006-profile-tab-pagination` | Fix profile "Likes" and "Replies" tab truncation (hardcoded `LIMIT 50`), add pagination and preserve active tab in query string |
| 7 | **Asad** | 22K-5147 | `007-login-throttling-security` | `feature/fix-007-login-throttling` | Add progressive delay and temporary 5-minute lockout after 5 consecutive failed login attempts to prevent brute-force attacks |
| 8 | **Saad** | 22K-5039 | `008-badge-sync-session-ux` | `feature/fix-008-badge-sync-session-ux` | Sync sidebar bell badge immediately on "Mark all as read" via AJAX, and provide friendly feedback on expired session AJAX calls |
| 9 | **Shahryar** | 22K-4435 | `009-regression-test-suite` | `feature/fix-009-regression-tests` | Write automated regression tests asserting all 8 bug fixes, ensuring 100% pass on SQLite and MySQL test suites |

---

## Detailed Bug Analysis & Solution Architecture

### 1. Muddassir Rizwan (23K-0784) — AI-DLC Quality Gates & Governance
- **Defect:** Team members might introduce scope creep or bypass memory bank documentation.
- **Fix:** Establish strict PR review checklist, keep `memory-bank/` artifacts synced with Git commits, and enforce code conventions.

### 2. Faiz Hussain (23K-2061) — Bookmark Counter Mismatch Bug
- **Defect:** `classes/Bookmark.php::countByUser()` runs `SELECT COUNT(*) FROM bookmarks WHERE user_id = :user_id`. When a bookmarked post is soft-deleted, `getBookmarkedPosts` hides it, but `countByUser()` still counts it. The UI displays "Your Bookmarks (5)" but only 3 cards appear.
- **Fix:** Update `countByUser()` with `JOIN posts p ON b.post_id = p.id WHERE p.deleted_at IS NULL`. Clean up orphaned bookmark rows or exclude them consistently.

### 3. Qaim Mehdi (23K-2075) — Moderation Notification & Pagination Defect
- **Defect:** 
  1. When an admin deletes a reported post in `report_review.php`, the post author receives no notification explaining that their post was removed for policy violation. The reporter also gets no resolution feedback.
  2. In `admin.php#reports`, `listPending(50, 0)` is hardcoded with no pagination pager.
- **Fix:** In `Report::review()`, trigger a system notification to the post author ("Your post was removed by administration for violating campus policies.") and to the reporter ("Your report has been reviewed."). Add pager controls to `admin.php#reports`.

### 4. Haseeb Asif (23K-0539) — Remember-Token Security Vulnerability
- **Defect:** When a user changes their password in `profile_action.php`, `destroySessionsForUser()` deletes database sessions, but does NOT remove records from `remember_tokens`. An attacker with a previously compromised remember cookie can still auto-login.
- **Fix:** Add `clearRememberTokensForUser($userId)` to `classes/User.php` and invoke it on password change and admin password reset.
- **Outcome (22 Nov 2026):** Investigation found no `remember_tokens` table in the schema and no remember-me cookie feature in the app — the defect does not exist. Password change already revokes other sessions; admin reset revokes all sessions. Unit closed as not-applicable (see the unit brief).

### 5. Muhammad Hadi (23K-0755) — Explore Search Repeating Users & Hashtag Casing
- **Defect:** 
  1. In `explore.php`, searching for a term returns both matching users and matching posts. On page 2 (`?q=term&page=2`), the same 20 user cards are rendered again at the top of the page.
  2. Hashtag queries with mixed casing (e.g., `#Hackathon2026` vs `#hackathon2026`) can fail on case-sensitive SQLite searches or duplicate database indexes.
- **Fix:** Only display `$foundUsers` when `$page === 1`. Normalize hashtag queries to lowercase before querying `post_hashtags`.

### 6. Yuneeb (23K-0733) — Profile Likes & Replies Truncation Bug
- **Defect:** In `profile.php`, while "Posts" has a Previous/Next pager, switching to the "Likes" or "Replies" tab limits results to 50 items (`LIMIT 50`). Furthermore, clicking pagination links drops the `&tab=likes` or `&tab=replies` URL query parameter.
- **Fix:** Add pagination support for Likes and Replies tabs on `profile.php`, and ensure `renderPager()` includes `&tab=` so page state is preserved.

### 7. Asad (22K-5147) — Brute-Force Login Rate Limiting
- **Defect:** An attacker can make unlimited rapid password guesses against `student@nu.edu.pk` in `login.php` with zero throttling.
- **Fix:** Use `Cache.php` (or in-memory/DB counter) to track failed login attempts per email/IP. After 5 consecutive failures, enforce a 5-minute lockout with a clear error message. Reset counter upon successful login.

### 8. Saad (22K-5039) — AJAX Bell Badge Desync & Session Expiry UX
- **Defect:** 
  1. Clicking "Mark all as read" in `notifications.php` marks notifications as read in the DB, but the red unread badge in the left sidebar navigation remains visible until the user manually refreshes the page.
  2. If a session expires while viewing a page, clicking Like or Bookmark silently fails.
- **Fix:** Update client-side JS in `assets/js/main.js` to immediately remove or set the sidebar badge count to 0 when "Mark all as read" succeeds. Catch HTTP 401/403 in AJAX handlers and display a friendly notification: "Your session has expired. Please log in again."

### 9. Shahryar (22K-4435) — Automated Regression Test Suite Expansion
- **Defect:** Currently, tests do not cover the 8 specific bug fixes above, leaving room for future regressions.
- **Fix:** Add automated test assertions in `tests/` verifying:
  - Bookmark count accuracy after post soft-deletion.
  - Remember token revocation on password change.
  - Moderation action notifications sent to author & reporter.
  - Login lockout trigger after 5 failed attempts.
  - Profile tab pagination and state preservation.
  Ensure `php tests/run_all.php` runs with 100% pass rate.
- **Outcome (22 Nov 2026):** Every fix shipped with its own tests — the suite is at 601 tests, 100% green on SQLite (local) and MySQL (CI). No duplicate regression file was added; see the unit brief for the check-by-check mapping.
