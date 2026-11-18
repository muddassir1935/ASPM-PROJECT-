---
unit: 002-bookmark-counter-fix
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
---

# Unit Brief: Bookmark Counter Inconsistency & Soft-Delete Filter Fix

## Assigned Owner
**Faiz Hussain** (23K-2061) · Branch: `feature/fix-002-bookmark-counter`

## Purpose
Fix the data inconsistency bug where `Bookmark::countByUser()` includes soft-deleted posts, causing the UI to display "Your Bookmarks (5)" while only 3 active cards render on the page.

## Scope
### In Scope
- Update `Bookmark::countByUser(int $userId)`: `JOIN posts p ON bookmarks.post_id = p.id WHERE bookmarks.user_id = :user_id AND p.deleted_at IS NULL`.
- Ensure `getBookmarkedIds()` consistently excludes soft-deleted post IDs.
- Update `bookmarks.php` to ensure badge count and list display are perfectly synchronized.

### Out of Scope
- Adding new social reaction types.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-2 | Fix bookmark counter mismatch with soft-deleted posts | Must |
