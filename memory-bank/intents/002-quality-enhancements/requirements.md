---
intent: 002-quality-enhancements
phase: inception
status: draft
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
---

# Requirements: Bug Fixes & Existing Feature Hardening

## Intent Overview
This intent focuses strictly on **bug fixes, edge-case resolution, security hardening, and reliability improvements** across CampusConnect's existing core features (Authentication, Feeds, Social Graph, Bookmarks, Notifications, Profile, Explore, and Moderation). **No new features are introduced.** Work is decomposed into 9 focused units assigned to the 9 team members.

## Business & Quality Goals

| Goal | Success Metric | Priority |
|------|----------------|----------|
| Fix Data Inconsistencies | Bookmark and profile counts accurately exclude soft-deleted posts | Must |
| Security Hardening | Invalidate persistent remember tokens on password change & add login throttling | Must |
| Moderation Transparency | Author and reporter notifications on report action/review | Must |
| Pagination & Edge Cases | Paginate profile tabs, notifications, and moderation reports (> 50 items) | Should |
| Zero Regressions | All 412+ tests pass with new test coverage for all 8 bug fixes | Must |

---

## Functional Requirements (Bug Fixes & Hardening)

### FR-1: AI-DLC Governance & Bugfix Validation
- **Owner**: Muddassir Rizwan (23K-0784)
- **Description**: Enforce the AI-DLC bugfix lifecycle, maintain Memory Bank consistency, ensure strict compliance with the zero-new-feature constraint, and review PR verification gates.
- **Priority**: Must
- **Assigned Unit**: `001-aidlc-governance`

### FR-2: Bookmark Counter Inconsistency with Soft-Deleted Posts
- **Owner**: Faiz Hussain (23K-2061)
- **Description**: Fix `Bookmark::countByUser()` and bookmark list views to properly exclude soft-deleted posts, resolving the bug where heading says "Your Bookmarks (N)" but fewer posts are displayed.
- **Priority**: Must
- **Assigned Unit**: `002-bookmark-counter-fix`

### FR-3: Moderation Action Feedback & Report Pagination
- **Owner**: Qaim Mehdi (23K-2075)
- **Description**: Notify post authors when their post is removed by admin moderation, notify reporters when their report is resolved, and add pagination to `admin.php#reports` for queues > 50 reports.
- **Priority**: Must
- **Assigned Unit**: `003-moderation-feedback-pagination`

### FR-4: Remember-Token Invalidation on Password Change & Reset
- **Owner**: Haseeb Asif (23K-0539)
- **Description**: Fix security loophole where changing or resetting a password revokes database sessions but leaves persistent `remember_tokens` active, allowing stolen remember cookies to re-login.
- **Priority**: Must
- **Assigned Unit**: `004-auth-token-invalidation`

### FR-5: Explore Search Pagination & Mixed-Case Hashtags
- **Owner**: Muhammad Hadi (23K-0755)
- **Description**: Fix user search repetition on Explore page 2+ (`?q=...&page=2`) so user cards only show on page 1, and ensure hashtag lookups handle mixed casing (e.g. `#FASTNUCES`) seamlessly across all SQL collations.
- **Priority**: Should
- **Assigned Unit**: `005-explore-search-casing-fix`

### FR-6: Profile Tabs Pagination (Likes & Replies) & Tab Query State
- **Owner**: Yuneeb (23K-0733)
- **Description**: Fix truncation bug on user profile where "Likes" and "Replies" tabs are hard-capped at 50 items, adding proper pagination while preserving the active tab query parameter across page navigation.
- **Priority**: Should
- **Assigned Unit**: `006-profile-tab-pagination`

### FR-7: Brute-Force Login Throttling & Progressive Backoff
- **Owner**: Asad (22K-5147)
- **Description**: Harden `login.php` against brute-force password guessing by introducing a progressive delay and 5-minute lockout after 5 consecutive failed attempts per IP/email via the Cache layer.
- **Priority**: Must
- **Assigned Unit**: `007-login-throttling-security`

### FR-8: Real-Time Header Badge Sync & AJAX Session Expiry UX
- **Owner**: Saad (22K-5039)
- **Description**: Fix UI bug where clicking "Mark all as read" in `notifications.php` leaves the sidebar bell badge unchanged until refresh, and provide a user-friendly toast/redirect when an AJAX action fails due to an expired session.
- **Priority**: Should
- **Assigned Unit**: `008-badge-sync-session-ux`

### FR-9: Comprehensive Automated Regression Tests for All Fixes
- **Owner**: Shahryar (22K-4435)
- **Description**: Author targeted automated test suites for each of the 8 bug fixes (asserting remember-token revocation, bookmark count accuracy, report notifications, login lockouts, and tab pagination) across both SQLite and MySQL.
- **Priority**: Must
- **Assigned Unit**: `009-regression-test-suite`
