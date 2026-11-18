---
story: 001-bookmark-count-sync
unit: 002-bookmark-counter-fix
intent: 002-quality-enhancements
status: completed
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T10:24:00Z
---

# Story: Synchronize Bookmark Counters with Active Posts

As a user browsing saved bookmarks,
I want the total count displayed on the Bookmarks page to match the actual number of visible saved posts,
So that I am not confused by ghost counts from deleted posts.

## Acceptance Criteria
- [x] `Bookmark::countByUser()` only counts bookmarks for posts where `deleted_at IS NULL`.
- [x] When a bookmarked post is soft-deleted, the bookmark counter decreases immediately.
- [x] No regression on SQLite or MySQL query execution.

## Verification
- Verified in `tests/SocialTest.php` (tests 38-41):
  - Soft-deleted posts drop out of `countByUser()`.
  - `isBookmarked()` returns `false` for soft-deleted posts.
  - Invalid `$userId <= 0` gracefully returns `0` without running queries.
  - `toggle()` on soft-deleted post safely returns `null`.
- Full suite passing: 65/65 in `SocialTest.php`, 412/412 overall test suite green.

## Estimate
2 story points
