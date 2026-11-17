# Decision Index — CampusConnect

Significant decisions, newest first. Each entry: what, when, why, where it
lives. Do not rewrite history here — append new decisions.

## 2026-11-17 — AI-DLC (specs.md) adopted for this repo

specs.md AI-DLC flow installed (version 0.1.74, Antigravity tooling) as the
project's agent workflow: `.specsmd/` framework + `memory-bank/` context +
`.agent/workflows/` slash commands + root `AGENTS.md`. Rationale: the course
teaches AI-DLC (ASPM Topics 1–3); the repo should demonstrate it, and future
work (if any) should run through phases, gates, and a memory bank instead of
ad-hoc prompting. Brownfield: the product was already submitted, so the memory
bank records the as-built state and the repo is in the Operations phase.

## 2026-11-16 — Submitted the team's own frozen build (424390e)

The submission (report + deck + zip) was built from main `424390e` (322 tests,
71 commits). Haseeb Asif's 7-commit audit pass landed on main later the same
day (`a3e1232`, 412 tests) — real fixes, but after the freeze, so it is NOT in
the submission. If the instructor opens the repo, the answer is: the audit
landed after the submission freeze; the submitted snapshot is `424390e`.

## 2026-11-15 — Post reporting + admin moderation queue (PR #15)

Qaim built reporting (7 commits) so his contribution count rose from 5 to 12
— a deliberate fairness move by the user, since the user had built nearly
everything alone. `reports` table + admin queue (dismiss / action with
soft-delete of the violating post).

## 2026-11-14 — Password management, no emailed reset (PR #12)

Change-own-password (current password required, other sessions revoked) and
admin reset (16-char temporary password shown once to the admin). No emailed
forgot-password link: the app has no mail infrastructure. Documented as a
known limitation, not hidden.

## 2026-11-13 — RBAC is real, not a label (PR #11)

Roles were a display label (signup always made `student`, `society` was never
checked). Built a central `Authorization` permission map (3 roles, 14
permissions, fail-closed), DB role refresh per request, admin panel, CSRF on
all state-changing forms/AJAX, login rate limit, session revocation. The user
raised this himself — roles must be enforced, not displayed.

## 2026-11-12 — Sprint 5 split for fairness

Original Sprint 5 remainder (notifications, responsive polish, SRS final,
manuals, contribution matrix, meetings log, seed data, deployment guide, deck,
demo script, report) assigned to Faiz + Qaim; Muse + user kept RBAC + audit
improvements and verification/fixing of teammates' work. Reason: the user had
built nearly everything alone; teammates needed real contributions for their
marks. Meeting logs and contribution details are never invented — only
verifiable git/chat history.

## 2026-11-12 — SQLite fallback must be LOUD (security audit H3)

The MySQL→SQLite fallback was silent, serving a different empty database.
Fixed with a visible banner. Principle: a fallback that changes the data the
user sees must announce itself.

## 2026-11-12 — Test-DB isolation (security audit H4)

Tests ran against the demo database and left 203 users / 179 posts of test
junk. Tests now use a throwaway SQLite file; the demo DB is re-seeded pristine
(7 users / 8 posts / 6 replies / 11 likes).

## 2026-11-12 — No phpredis required

Caching uses `TinyRedisClient` (pure-PHP RESP) when the phpredis extension is
absent, with an in-memory fallback. Rationale: XAMPP on Windows has no
phpredis by default; requiring it would break the demo setup.

## 2026-11-09 — Hashtag linkification must not break apostrophes

Sprint 4 bug (fixed in PR #8): the hashtag regex broke post text containing
apostrophes. Lesson recorded: text transforms must be tested against real
user text, not just clean examples.

## 2026-11-07 — Sync before work (shared repo rule)

Before any work on this repo: fetch, read new commits + authors, active
branches, open PRs — then decide. Never duplicate a teammate's feature, never
overwrite their work. Proven twice (Sprint 4 job waited on sync instead of
rebuilding Faiz's merged work).

## 2026-11-03 — Merge rule: verified branches merge via PR

When a feature branch's work is finished, verified, and push-approved, it is
merged into main via PR without a separate merge go-ahead. Exception: the
final release tag needs the user's explicit approval (still pending).

## 2026-11-03 — No direct main commits; one branch per feature

`feature/sprint-<n>-<name>`; iterate in the same branch, never a new branch
per iteration of the same work.
