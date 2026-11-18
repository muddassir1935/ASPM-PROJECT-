---
unit: 008-badge-sync-session-ux
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
---

# Unit Brief: Header Badge Sync & AJAX Session Expiry UX

## Assigned Owner
**Saad** (22K-5039) · Branch: `feature/fix-008-badge-sync-session-ux`

## Purpose
Fix client-side desync where "Mark all as read" clears DB notifications but leaves the left sidebar red bell badge visible until page reload, and improve expired session handling during AJAX actions.

## Scope
### In Scope
- In `notifications.php` / `assets/js/main.js`: When "Mark all as read" succeeds, immediately find the navigation notification badge and set text to 0 or remove `.has-unread` class in the DOM.
- In AJAX response handlers (Like, Bookmark, Follow): When server returns HTTP 401 or 403 (session expired), display a clear floating toast alert: "Your session has expired. Please refresh and log in again." instead of failing silently.

### Out of Scope
- Adding WebSocket connections.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-8 | Synchronize navigation badge on mark-all-read and surface session expiry alerts | Should |
