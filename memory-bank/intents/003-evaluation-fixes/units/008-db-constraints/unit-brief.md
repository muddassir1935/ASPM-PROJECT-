---
unit: 008-db-constraints
intent: 003-evaluation-fixes
phase: inception
status: completed
created: 2026-11-18T08:00:00Z
updated: 2026-11-19T05:30:00Z
---

# Unit Brief: ENUM/CHECK Constraints on reports.reason + reports.status

## Assigned Owner
**Unassigned** — to be allocated at the planning session · Branch: `feature/fix-003-db-constraints`

## Purpose
Fix the unconstrained-columns deduction (−0.5 marks): `reports.reason` (VARCHAR(32)) and `reports.status` (VARCHAR(16) DEFAULT 'pending') accept any text today.

## Scope
### In Scope
- MySQL migration adding ENUM (or CHECK) constraints: `reason` limited to the real report reasons, `status` to the values the code actually uses (`pending` / `dismissed` / `actioned` — `Report::STATUS_*`; `reviewed` is not a stored status).
- SQLite schema + tests updated the same way; existing seed data still imports.

### Out of Scope
- Report-workflow changes. New report reasons.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-8 | Constrain reports.reason + reports.status (DB-1) | Must |

## Estimate
2 story points (Must). Estimates are sized from the code; the owner confirms or re-sizes at the planning session.
