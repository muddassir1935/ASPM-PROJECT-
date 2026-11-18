---
story: 001-notification-fk
unit: 009-db-fk-reference
intent: 003-evaluation-fixes
status: ready
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:00:00Z
---

# Story: Notification References Are Protected by the Database

As a developer,
I want `notifications.reference_id` protected by the database,
So that a notification cannot point at a deleted post.

## Acceptance Criteria
- [ ] A FK is added that is safe for the polymorphic column: `reference_id` holds a post id for like/reply notifications but a user id for follow notifications (`Follow.php:55`), so a plain FK to `posts(id)` would break follows. The FK is scoped to post-type notifications (or the reference is split per type).
- [ ] A test proves follow notifications still work after the change.
- [ ] The existing read-time guard stays as defence in depth; migration + SQLite schema + tests; suite green.

## Estimate
3 story points · Priority: Must

## Verification
- Deleting (or soft-deleting) a post leaves no dangling post-type notification reference; follow notifications unaffected (tests).
- Full suite green.
