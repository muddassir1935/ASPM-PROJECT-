---
unit: 006-profile-tab-pagination
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
---

# Unit Brief: Profile Tabs Pagination (Likes & Replies) & Tab Query State

## Assigned Owner
**Yuneeb** (23K-0733) · Branch: `feature/fix-006-profile-tab-pagination`

## Purpose
Fix truncation on user profiles where "Likes" and "Replies" tabs are hard-capped at 50 items with no pagination, and ensure the active tab is preserved when paging through results.

## Scope
### In Scope
- In `profile.php`:
  - Replace hardcoded `LIMIT 50` on `$likedPosts` and `$userReplies` with standard pagination (`$perPage = 20`, `$page = clampPage(...)`).
  - Pass the active tab (`&tab=likes` or `&tab=replies`) into `renderPager()` so clicking "Older &rarr;" stays on the active tab instead of reverting to Posts.
- Maintain empty states for each tab when no items exist.

### Out of Scope
- Adding new tabs or profile themes.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-6 | Add pagination to Likes and Replies tabs on profile and preserve tab in pager | Should |
