---
unit: 005-faceted-search
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Unit Brief: Faceted Search & Discovery Filtering

## Assigned Owner
**Muhammad Hadi** (23K-0755) · Branch: `feature/unit-005-faceted-search`

## Purpose
Enhance campus content discovery on `explore.php` by allowing students to filter search results by role (Student vs Society), post type (Announcements vs Regular), and date recency.

## Scope
### In Scope
- Upgrade `explore.php` with filter pills: `All`, `Announcements`, `People`, `Societies`, `Hashtags`.
- Extend `classes/Post.php::searchPosts()` and `classes/User.php::searchUsers()` to accept filter parameters.
- Ensure safe SQL escaping with `ESCAPE '!'` to maintain 100% MySQL 8/9 and SQLite compatibility.
- Preserve active filter state across pagination links.

### Out of Scope
- Full-text Elasticsearch/Sphinx engine (standard indexed SQL query filters).

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-5 | Multi-facet search filtering with role and announcement tabs on Explore | Should |
