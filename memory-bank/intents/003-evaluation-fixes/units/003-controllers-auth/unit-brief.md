---
unit: 003-controllers-auth
intent: 003-evaluation-fixes
phase: construction
status: completed
created: 2026-11-18T08:00:00Z
updated: 2026-11-21T19:45:00Z
---

# Unit Brief: Controllers for Auth + Profile Actions

## Assigned Owner
**Unassigned** — to be allocated at the planning session · Branch: `feature/fix-003-controllers-auth`

## Purpose
Fix the procedural-pages deduction (−1 mark, part 1 of 3): move login, registration, profile and password request handling out of page scripts into controller classes.

## Scope
### In Scope
- New `classes/AuthController.php` (login, registration, logout) and `classes/ProfileController.php` (profile update, password change, avatar/cover).
- The `*_action.php` pages and form handlers become thin entry points (include + delegate).
- CSRF, validation, redirects and session behaviour unchanged.

### Out of Scope
- Auth logic changes (no new rules, no password-policy change). UI changes.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-3 | Controllers for auth + profile actions (OOP-3) | Must |

## Estimate
5 story points (Must). Estimates are sized from the code; the owner confirms or re-sizes at the planning session.
