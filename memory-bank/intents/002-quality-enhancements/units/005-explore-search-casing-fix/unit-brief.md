---
unit: 005-explore-search-casing-fix
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
---

# Unit Brief: Explore Search Pagination & Mixed-Case Hashtags

## Assigned Owner
**Muhammad Hadi** (23K-0755) · Branch: `feature/fix-005-explore-search-casing`

## Purpose
Fix user search results repeating at the top of every subsequent Explore page (`?q=...&page=2`), and fix mixed-case hashtag query matching across SQLite and MySQL collations.

## Scope
### In Scope
- In `explore.php`: Only show `$foundUsers` on page 1 (`$page === 1`). Page 2+ displays only paginated post results.
- In `classes/Hashtag.php`: Normalize incoming tags to lowercase (`mb_strtolower(trim($tag), 'UTF-8')`) in `getPostsByTag()` and extraction regex so `#FASTNUCES` and `#fastnuces` always resolve identically.
- Ensure hashtag links preserve correct lowercase formatting.

### Out of Scope
- Adding new search filter tabs or full-text search indexing engines.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-5 | Restrict user search results to page 1 and normalize hashtag casing | Should |
