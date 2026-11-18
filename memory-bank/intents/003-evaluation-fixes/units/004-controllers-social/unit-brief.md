---
unit: 004-controllers-social
intent: 003-evaluation-fixes
phase: inception
status: ready
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:08:17Z
---

# Unit Brief: Controllers for Post + Social Actions

## Assigned Owner
**Unassigned** — to be allocated at the planning session · Branch: `feature/fix-003-controllers-social`

## Purpose
Fix the procedural-pages deduction (−1 mark, part 2 of 3): move post, like, follow, reply and bookmark request handling into controllers.

## Scope
### In Scope
- `PostController` (already exists) extended or complemented by a new `classes/SocialController.php` covering like/follow/reply/bookmark actions.
- The social `*_action.php` pages become thin entry points; AJAX responses unchanged.

### Out of Scope
- Feed ranking, hashtag, or notification logic changes. UI changes.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-4 | Controllers for post + social actions (OOP-3) | Must |

## Estimate
5 story points (Must). Estimates are sized from the code; the owner confirms or re-sizes at the planning session.
