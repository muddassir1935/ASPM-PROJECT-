---
story: 001-search-filter-facets
unit: 005-faceted-search
intent: 002-quality-enhancements
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Story: Explore Filter Tabs & Search Facets

As a student searching for campus information,
I want to filter search results by "Announcements Only" or "Societies Only",
So that I can quickly find official notices without sifting through casual posts.

## Acceptance Criteria
- [ ] Explore page presents category tabs: "Top", "Latest", "People", "Announcements".
- [ ] Selecting "Announcements" limits feed query to `is_announcement = 1`.
- [ ] Selecting "People" displays user cards with follow/unfollow buttons.
- [ ] Active filter is preserved when navigating through pagination pages.
