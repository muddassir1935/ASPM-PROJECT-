---
unit: 010-schema-generator
intent: 003-evaluation-fixes
phase: inception
status: completed
created: 2026-11-18T08:00:00Z
updated: 2026-11-22T00:35:00Z
---

# Unit Brief: Single Source of Truth for the SQLite Schema

## Assigned Owner
**Qaim Mehdi** · Branch: `feature/fix-003-db-fk-reference`

## Purpose
Fix the duplicated-schema deduction (−0.5 marks): the SQLite schema is hand-copied in `config/Database.php` (12 CREATE TABLEs, ~lines 61–180) and can drift from `db/schema.sql`. Generate it from the MySQL schema instead.

## Scope
### In Scope
- A generator script producing the SQLite DDL from `db/schema.sql` — translating MySQL-isms (AUTO_INCREMENT, ENGINE/CHARSET, TIMESTAMP defaults).
- Replaces the 12 hand-written CREATE TABLEs in `config/Database.php`.
- Generated schema covers the same 12 tables/columns, **plus** the unit 008/009 constraints — so this unit runs after 008 + 009.
- Tests run against the generated schema.

### Out of Scope
- Schema changes beyond the generator + the 008/009 constraints. MySQL-side changes.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-10 | Single source of truth for the SQLite schema (DB-3) | Must |

## Estimate
5 story points (Must). Depends on units 008 + 009. Estimates are sized from the code; the owner confirms or re-sizes at the planning session.
