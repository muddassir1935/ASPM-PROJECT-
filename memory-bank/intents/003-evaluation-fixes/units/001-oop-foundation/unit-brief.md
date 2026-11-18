---
unit: 001-oop-foundation
intent: 003-evaluation-fixes
phase: inception
status: ready
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:08:17Z
---

# Unit Brief: Base Model Class & Custom Interfaces

## Assigned Owner
**Unassigned** — to be allocated at the planning session · Branch: `feature/fix-003-oop-foundation`

## Purpose
Fix Sir's biggest OOP deduction (−2 marks): 18 real classes, none extends another, no custom interfaces. Introduce a shared `Model` base class and the design's own interfaces, starting with the cache drivers.

## Scope
### In Scope
- New `classes/Model.php`: abstract base holding the PDO connection + shared helpers (e.g. fetch helpers, `findById`).
- The ~10 model classes that repeat the same constructor shape extend it (8 use `public function __construct(PDO $dbConnection)`, 2 use `__construct(PDO $db)`).
- New `CacheDriverInterface` (get/set/delete) implemented by the Redis and in-memory drivers (unlocks unit 002).
- Report §3 updated to describe the hierarchy honestly.

### Out of Scope
- Changing any behaviour, query, or UI. No new features.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-1 | Base Model class + custom interfaces (OOP-1) | Must |

## Estimate
8 story points (Must). Estimates are sized from the code; the owner confirms or re-sizes at the planning session.
