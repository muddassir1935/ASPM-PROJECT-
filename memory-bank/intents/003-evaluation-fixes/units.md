---
intent: 003-evaluation-fixes
phase: inception
status: units-decomposed
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:08:17Z
---

# Evaluation Fixes — Unit Decomposition

## Strict Scope Policy: Fixes Only, Zero New Features
All 10 units address Sir's exact OOP + DB deductions. No new features, no new tables, no new roles.

## Units Overview (10 Units)

**Assignment:** units are unassigned for now — allocation is decided at the planning session. The intent is planning only at this stage.

| Unit ID | Unit Name | Assigned Member | Roll Number | Focus Area |
|---------|-----------|-----------------|-------------|------------|
| `001-oop-foundation` | Base Model Class & Custom Interfaces | Unassigned | — | `classes/Model.php` (new), model classes |
| `002-cache-chain` | Real Chain of Responsibility (Cache) | Unassigned | — | `classes/Cache.php`, cache drivers |
| `003-controllers-auth` | Auth + Profile Controllers | Unassigned | — | `classes/AuthController.php`, `ProfileController.php` (new) |
| `004-controllers-social` | Post + Social Controllers | Unassigned | — | `classes/PostController.php`, `SocialController.php` (new) |
| `005-controllers-admin` | Admin + Moderation Controllers | Unassigned | — | `classes/AdminController.php` (new) |
| `006-helpers-objects` | Static Helpers → Injectable Objects | Unassigned | — | `classes/Auth.php`, `Csrf.php`, `Authorization.php`, `SafeRedirect.php` |
| `007-phpunit-migration` | Test Harness → PHPUnit (Could) | Unassigned | — | `tests/`, `tests/run_all.php` |
| `008-db-constraints` | reports ENUM/CHECK Constraints | Unassigned | — | `db/migrate_*.sql`, `db/schema.sql` |
| `009-db-fk-reference` | FK on notifications.reference_id | Unassigned | — | `db/migrate_*.sql`, `classes/Notification.php` |
| `010-schema-generator` | SQLite Schema Generator | Unassigned | — | `config/Database.php`, generator script |

## Estimates & Priorities (from the planning pack)

| Unit | Estimate (pts) | Priority | Marks addressed |
|------|---------------|----------|-----------------|
| 001-oop-foundation | 8 | Must | OOP −2 |
| 002-cache-chain | 3 | Should | OOP −0.5 |
| 003-controllers-auth | 5 | Must | OOP −1 (shared) |
| 004-controllers-social | 5 | Must | OOP −1 (shared) |
| 005-controllers-admin | 3 | Must | OOP −1 (shared) |
| 006-helpers-objects | 5 | Should | OOP −0.5 (shared) |
| 007-phpunit-migration | 13 | Could | OOP −0.5 (shared) |
| 008-db-constraints | 2 | Must | DB −0.5 |
| 009-db-fk-reference | 3 | Must | DB −0.5 |
| 010-schema-generator | 5 | Must | DB −0.5 |

Must + Should: **39 points** (up to 5.5 marks). With Could: 52 points.

## Execution Order (backlog order, not unit-number order)

1. `008-db-constraints` (2) — quick win
2. `009-db-fk-reference` (3)
3. `001-oop-foundation` (8) — unlocks `002-cache-chain`
4. `002-cache-chain` (3)
5. `010-schema-generator` (5) — after 008 + 009
6. `003-controllers-auth` (5)
7. `004-controllers-social` (5)
8. `005-controllers-admin` (3)
9. `006-helpers-objects` (5)
10. `007-phpunit-migration` (13) — Could, only if time remains

## Sprint Plan (re-evaluation Tue 24 Nov 2026)

- **Sprint A — Wed 18 – Fri 20 Nov (21 pts):** 008, 009, 001, 002, 010
- **Sprint B — Sat 21 – Mon 23 Nov (18 pts):** 003, 004, 005, 006
- **Mon 23 Nov EOD: code freeze.** **Tue 24 Nov: presentation + Sir's review.**

## Unit Dependency Graph

```text
[008-db-constraints]  [009-db-fk-reference]  [001-oop-foundation]
        │                       │                       │
        └───────────┬───────────┘                       │
                    ▼                                   ▼
         [010-schema-generator]              [002-cache-chain]
                                                        │
[003-controllers-auth] [004-controllers-social] [005-controllers-admin]
        │                       │                       │
        └───────────────────────┼───────────────────────┘
                                ▼
                    [006-helpers-objects]
                                │
                                ▼
                    [007-phpunit-migration] (Could)
```
