---
unit: 006-accessibility-theme
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Unit Brief: WCAG 2.1 AA Accessibility & Database Theme Persistence

## Assigned Owner
**Yuneeb** (23K-0733) · Branch: `feature/unit-006-accessibility-theme`

## Purpose
Ensure CampusConnect meets WCAG 2.1 Level AA standards for accessible keyboard navigation and screen readers, while persisting theme choices directly to the user's database profile.

## Scope
### In Scope
- DB Column: Add `theme_preference` (`VARCHAR(16) DEFAULT 'system'`) to `users` table.
- Synchronize frontend theme switch with backend persistence (`profile_action.php` / AJAX).
- Modal accessibility: Trap tab focus inside modals (Report Post, Delete Confirmation), dismiss on `Esc` key, and restore focus on close.
- Ensure 4.5:1 color contrast ratio across both light and dark themes.

### Out of Scope
- Custom font uploading.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-6 | Persist theme preference to DB and enforce WCAG 2.1 AA modal accessibility | Should |
