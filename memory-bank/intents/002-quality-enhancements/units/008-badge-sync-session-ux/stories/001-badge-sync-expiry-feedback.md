---
story: 001-badge-sync-expiry-feedback
unit: 008-badge-sync-session-ux
intent: 002-quality-enhancements
status: ready
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
---

# Story: Instant Bell Badge Dismissal & Session Expiry Feedback

As a student reviewing notifications,
I want the unread notification badge in the sidebar to vanish the moment I click "Mark all as read",
And if my session expires while browsing, I want an alert telling me to log in again rather than silent button failure.

## Acceptance Criteria
- [x] Clicking "Mark all as read" clears the navigation badge count in the DOM without reload.
- [x] AJAX failure due to 401/403 triggers a visible floating warning message.

## Estimate
3 story points
