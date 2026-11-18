---
story: 001-sqlite-schema-generator
unit: 010-schema-generator
intent: 003-evaluation-fixes
status: ready
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:00:00Z
---

# Story: The SQLite Schema Is Generated from db/schema.sql

As a developer,
I want the SQLite schema generated from `db/schema.sql`,
So that the two schemas cannot drift apart again.

## Acceptance Criteria
- [ ] A generator (script) produces the SQLite DDL from `db/schema.sql` — translating MySQL-isms (AUTO_INCREMENT, ENGINE/CHARSET, TIMESTAMP defaults) — replacing the 12 hand-written CREATE TABLEs in `config/Database.php`.
- [ ] Generated schema is byte-identical in coverage to today's hand-written one (same 12 tables, same columns), plus the unit 008/009 constraints.
- [ ] Tests run against the generated schema; suite green.

## Estimate
5 story points · Priority: Must · Depends on: units 008 + 009

## Verification
- Diff of generated vs hand-written schema shows only the intended additions (008/009 constraints).
- Full suite green against the generated schema.
