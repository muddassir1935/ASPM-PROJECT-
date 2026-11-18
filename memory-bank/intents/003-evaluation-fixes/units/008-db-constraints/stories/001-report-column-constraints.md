---
story: 001-report-column-constraints
unit: 008-db-constraints
intent: 003-evaluation-fixes
status: ready
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:00:00Z
---

# Story: Report Reason and Status Are Constrained by the Database

As a developer,
I want `reports.reason` and `reports.status` constrained by the database,
So that invalid values cannot be stored.

## Acceptance Criteria
- [ ] MySQL migration adds ENUM (or CHECK) constraints: `reason` limited to the real report reasons, `status` to `pending/reviewed/dismissed` (match the values the code actually uses).
- [ ] SQLite schema + tests updated the same way; existing seed data still imports; suite green.

## Estimate
2 story points · Priority: Must

## Verification
- Inserting an invalid reason/status fails on MySQL and SQLite (test asserting the constraint rejects bad values).
- Full suite green; `db/seed.sql` still imports cleanly.
