---
unit: 009-db-fk-reference
intent: 003-evaluation-fixes
phase: inception
status: ready
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:08:17Z
---

# Unit Brief: Foreign Key on notifications.reference_id

## Assigned Owner
**Unassigned** — to be allocated at the planning session · Branch: `feature/fix-003-db-fk-reference`

## Purpose
Fix the missing-foreign-key deduction (−0.5 marks): `notifications.reference_id` has no FK today, so a notification can point at a deleted post.

## Scope
### In Scope
- A FK that is **safe for the polymorphic column**: `reference_id` holds a post id for like/reply notifications but a **user id** for follow notifications (`Follow.php:55`), so a plain FK to `posts(id)` would break follows. Scope the FK to post-type notifications (or split the reference per type) — decided during implementation, with a test proving follow notifications still work.
- Keep the existing read-time guard (notifications for soft-deleted posts are already filtered in `Notification.php`) as defence in depth.
- Migration + SQLite schema + tests.

### Out of Scope
- Notification-type redesign beyond what the FK needs. UI changes.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-9 | Foreign key on notifications.reference_id (DB-2) | Must |

## Estimate
3 story points (Must). Estimates are sized from the code; the owner confirms or re-sizes at the planning session.
