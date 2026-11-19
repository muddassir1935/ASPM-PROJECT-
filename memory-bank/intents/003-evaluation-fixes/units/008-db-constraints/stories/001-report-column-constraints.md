---
story: 001-report-column-constraints
unit: 008-db-constraints
intent: 003-evaluation-fixes
status: completed
created: 2026-11-18T08:00:00Z
updated: 2026-11-19T05:30:00Z
---

# Story: Report Reason and Status Are Constrained by the Database

As a developer,
I want `reports.reason` and `reports.status` constrained by the database,
So that invalid values cannot be stored.

## Acceptance Criteria
- [x] MySQL migration adds ENUM (or CHECK) constraints: `reason` limited to the real report reasons (`spam/harassment/misinformation/inappropriate/violence/other`), `status` to `pending/dismissed/actioned` (the values the code actually uses — `Report::STATUS_*`; see correction note below).
- [x] SQLite schema + tests updated the same way; existing seed data still imports; suite green.

> Correction (19 Nov 2026, found during implementation): the story as written said `pending/reviewed/dismissed`, but `reviewed` is never a stored status — `grep` finds it only in method names (`listReviewed`). The code writes `pending`, `dismissed` and `actioned` (`Report.php:39-41`, review flows at :402-431, counts at :281/:334). An ENUM with `reviewed` instead of `actioned` would have broken the action flow. Implemented with `pending/dismissed/actioned` instead.

## Estimate
2 story points · Priority: Must

## Verification
- Inserting an invalid reason/status fails on MySQL and SQLite (test asserting the constraint rejects bad values).
- Full suite green; `db/seed.sql` still imports cleanly.
