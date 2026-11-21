---
unit: 006-helpers-objects
intent: 003-evaluation-fixes
phase: construction
status: completed
created: 2026-11-18T08:00:00Z
updated: 2026-11-21T20:30:00Z
---

# Unit Brief: Static Helpers Become Injectable Objects

## Assigned Owner
**Unassigned** — to be allocated at the planning session · Branch: `feature/fix-003-helpers-objects`

## Purpose
Fix the static-helpers half of the OOP-4 deduction (−0.5 marks, shared with the home-made harness): Auth, Csrf, Authorization and SafeRedirect are static utility classes today; make them real objects passed in through constructors.

## Scope
### In Scope
- Convert the four classes (7 + 5 + 6 + 3 static methods) to instantiable classes with instance methods.
- Call sites receive them via constructors (constructor injection); no behaviour change.

### Out of Scope
- The PHPUnit migration (unit 007). Auth/RBAC rule changes.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-6 | Static helpers become injectable objects (OOP-4) | Should |

## Estimate
5 story points (Should). Estimates are sized from the code; the owner confirms or re-sizes at the planning session.
