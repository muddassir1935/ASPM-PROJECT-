---
story: 001-bookmark-count-sync
unit: 002-bookmark-counter-fix
intent: 002-quality-enhancements
status: ready
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
---

# Story: Synchronize Bookmark Counters with Active Posts

As a user browsing saved bookmarks,
I want the total count displayed on the Bookmarks page to match the actual number of visible saved posts,
So that I am not confused by ghost counts from deleted posts.

## Acceptance Criteria
- [ ] `Bookmark::countByUser()` only counts bookmarks for posts where `deleted_at IS NULL`.
- [ ] When a bookmarked post is soft-deleted, the bookmark counter decreases immediately.
- [ ] No regression on SQLite or MySQL query execution.
