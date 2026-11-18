---
unit: 007-phpunit-migration
intent: 003-evaluation-fixes
phase: inception
status: ready
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:08:17Z
---

# Unit Brief: Test Harness Migration to PHPUnit

## Assigned Owner
**Unassigned** — to be allocated at the planning session · Branch: `feature/fix-003-phpunit`

## Purpose
Fix the home-made-harness half of the OOP-4 deduction (−0.5 marks, shared with static helpers): the 412-test suite runs on a custom `run_all.php` + `assertTest` harness; migrate it to PHPUnit, the standard tool.

## Scope
### In Scope
- All suites run under PHPUnit with the same assertions and the same pass count.
- `run_all.php` retired or kept as a thin wrapper.

### Out of Scope
- New test coverage (intent 002 unit 009 owns regression tests). Behaviour changes.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-7 | Migrate the test harness to PHPUnit (OOP-4) | Could |

## Estimate
13 story points (Could). **Trade-off:** the largest estimate in the intent for a share of 0.5 marks — do only if all Musts and Shoulds are done with time to spare. If dropped, record it as an explicit decision in the retro, not an omission. Estimates are sized from the code; the owner confirms or re-sizes at the planning session.
