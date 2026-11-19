---
story: 001-profile-tab-pager
unit: 006-profile-tab-pagination
intent: 002-quality-enhancements
status: completed
created: 2026-11-18T09:10:00Z
updated: 2026-11-19T15:45:00Z
---

# Story: Profile Tab Pagination & State Preservation

As a student viewing my profile's Liked Posts or Replies,
I want to browse through all my past interactions across multiple pages,
And I want clicking Next/Previous to stay on the Liked Posts tab.

## Acceptance Criteria
- [x] Liked Posts tab paginates 20 items per page with Previous/Next controls.
- [x] Replies tab paginates 20 items per page with Previous/Next controls.
- [x] Pager links include `&tab=likes` or `&tab=replies` dynamically. (No Replies tab existed; Muddassir approved building it on 19 Nov 2026 as the one exception to the fixes-only rule. Both tabs now keep their tab when paging.)

## Estimate
5 story points

## Completion Notes
- Likes tab pagination: done earlier (PR #24).
- Replies tab (branch `feature/fix-006-replies-tab`): `Reply::getRepliesByUser()` / `Reply::countByUser()` (newest first, excludes soft-deleted replies and replies on soft-deleted posts); `profile.php` shows a public Replies tab with 20 per page, `clampPage`, empty state "No replies yet.", and `renderPager(...&tab=replies)`.
- `tests/ProfileRepliesTest.php` (16 tests) added to `tests/run_all.php`; full suite green.
