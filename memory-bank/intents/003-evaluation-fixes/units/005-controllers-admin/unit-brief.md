---
unit: 005-controllers-admin
intent: 003-evaluation-fixes
phase: inception
status: completed
created: 2026-11-18T08:00:00Z
updated: 2026-11-22T00:45:00Z
---

# Unit Brief: Controllers for Admin + Moderation Actions

## Assigned Owner
**Unassigned** — to be allocated at the planning session · Branch: `feature/fix-003-controllers-admin`

## Purpose
Fix the procedural-pages deduction (−1 mark, part 3 of 3): move admin and report-review request handling into a controller.

## Scope
### In Scope
- New `classes/AdminController.php` owning role changes, deactivation, report review and password resets.
- `admin.php` / `report_review.php` become thin entry points.
- RBAC checks stay enforced via `Auth::can()` / `Authorization::can()`.

### Out of Scope
- Permission-model changes. Moderation-policy changes. UI changes.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-5 | Controllers for admin + moderation actions (OOP-3) | Must |

## Estimate
3 story points (Must). Estimates are sized from the code; the owner confirms or re-sizes at the planning session.
