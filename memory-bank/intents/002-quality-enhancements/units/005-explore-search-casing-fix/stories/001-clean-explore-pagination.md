---
story: 001-clean-explore-pagination
unit: 005-explore-search-casing-fix
intent: 002-quality-enhancements
status: ready
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
---

# Story: Clean Explore Pagination & Hashtag Casing Normalization

As an active student searching the explore feed,
I want page 2 of my search to show older matching posts without cluttering the screen with duplicate user profile cards,
And I want clicking uppercase hashtags like `#FAST` to match `#fast` posts consistently.

## Acceptance Criteria
- [x] `$foundUsers` renders only when `$page === 1`.
- [x] On page 2+, only matching post cards are rendered with the Previous/Next pager.
- [x] Searching `#FAST` or `#Fast` matches posts tagged `#fast` seamlessly on both SQLite and MySQL.

## Estimate
3 story points
