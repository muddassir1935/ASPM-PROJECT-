---
intent: 002-quality-enhancements
phase: inception
status: units-decomposed
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
---

# Bug Fixes & Hardening — Unit Decomposition

## Strict Scope Policy: Zero New Features
All 9 units address genuine bugs, edge cases, reliability gaps, and security hardening strictly within existing features.

## Units Overview (9 Team Members Division)

| Unit ID | Unit Name | Assigned Member | Roll Number | Focus Area |
|---------|-----------|-----------------|-------------|------------|
| `001-aidlc-governance` | AI-DLC Governance & Quality Gates | Muddassir Rizwan | 23K-0784 | Process, memory bank, PR reviews |
| `002-bookmark-counter-fix` | Bookmark Count & Soft-Delete Filter | Faiz Hussain | 23K-2061 | `classes/Bookmark.php`, `bookmarks.php` |
| `003-moderation-feedback-pagination` | Moderation Feedback & Report Pager | Qaim Mehdi | 23K-2075 | `classes/Report.php`, `admin.php` |
| `004-auth-token-invalidation` | Remember Token Invalidation on Password Reset | Haseeb Asif | 23K-0539 | `classes/User.php`, `profile_action.php` |
| `005-explore-search-casing-fix` | Explore Pagination & Hashtag Casing | Muhammad Hadi | 23K-0755 | `explore.php`, `classes/Hashtag.php` |
| `006-profile-tab-pagination` | Profile Likes/Replies Tab Pagination | Yuneeb | 23K-0733 | `profile.php`, `classes/Post.php` |
| `007-login-throttling-security` | Brute-Force Login Throttling & Lockout | Asad | 22K-5147 | `login.php`, `classes/User.php` |
| `008-badge-sync-session-ux` | Header Badge Sync & AJAX Session Expiry | Saad | 22K-5039 | `includes/header.php`, `assets/js/main.js` |
| `009-regression-test-suite` | Regression Test Automation for All 8 Fixes | Shahryar | 22K-4435 | `tests/`, `tests/run_all.php` |

---

## Unit Dependency Graph

```text
[001-aidlc-governance] (Framework & Verification Gates)
      │
      ├───────────────────────┬───────────────────────┐
      ▼                       ▼                       ▼
[002-bookmark-counter-fix] [004-auth-token-invalidation] [003-moderation-feedback-pagination]
      │                       │                       │
      ├───────────────────────┼───────────────────────┤
      ▼                       ▼                       ▼
[005-explore-search-casing] [007-login-throttling-sec]   [008-badge-sync-session-ux]
      │                       │                       │
      └───────────────────────┼───────────────────────┘
                              ▼
                 [006-profile-tab-pagination]
                              │
                              ▼
                 [009-regression-test-suite]
```
