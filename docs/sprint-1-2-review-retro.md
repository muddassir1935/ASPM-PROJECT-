# Sprint 1–2 Review and Retro Notes

> Honesty note: no separate standup/meeting minutes were kept during Sprints
> 1–2, so none are invented here. This record is reconstructed from the git
> history, the two merged pull requests, and the approved sprint plan, and is
> dated by the evidence, not backdated. Dates below are commit/merge dates.

## Sprint 1 review — Auth (WBS 1.0)

- **Delivered:** repo + branching model, PDO Singleton `Database`, `users`
  schema with unique email, registration with `password_hash()`, login with
  error handling, `Auth` session safeguards, logout, and `tests/UserTest.php`.
  Merged to `main` 2 Nov 2026 (merge `b2a72b4`); the underlying auth commits
  date from 6 Oct – 21 Oct 2026.
- **Verified:** `UserTest` suite green on 2 Nov 2026 (17/17).
- **Known gaps at Sprint 1 close:** no separate `sessions` table (native PHP
  sessions used), no 3-column layout, no design system beyond basic variables,
  no SRS sections, no diagrams, no written review/retro records. The first two
  gaps became approved decisions (see below) rather than defects to hide.

## Sprint 2 review — Post Engine & Feed (WBS 2.0)

- **Delivered in two PRs, both merged 2 Nov 2026:**
  - PR #1 (merge `cc7c1eb`): post-engine backend — `posts` schema + indexes,
    `Post` model with full CRUD and validation, `PostController`, feed query
    with pagination, validation tests (commit `50dd377`).
  - PR #2 (merge `f496a09`): feed UI — live timeline, compose box with
    280-char counter, post detail page, owner edit/delete, soft-delete via
    `deleted_at`, signup live validation (commit `4137b73`).
- **Verified 2 Nov 2026:** `PostTest` 29/29 and `UserTest` 17/17 run locally;
  a full browser flow (signup → login → compose → feed → detail → edit →
  soft-delete) plus an XSS rendering check (script-tag post shown as safe text)
  passed. Screenshots of that flow were shown to the user the same day.
- **Scope note:** the original plan listed Sprint 2 as "backend done, UI
  missing". Both halves actually landed on 2 Nov, so Sprint 2 closed
  functionally complete in `main`; only this documentation increment remained,
  which is what the `feature/sprint-2-docs` branch closes.

## What went well

- Small, reviewable PRs: backend first, UI second, each merged only after
  verification — the same gate this docs branch follows (no push/merge without
  user approval).
- Tests run in the workspace (PHP + SQLite fallback) instead of depending on
  the demo machine's XAMPP, so verification no longer waits for a manual step.
- Soft-delete replaced the original hard delete without breaking the feed:
  every read query filters `deleted_at IS NULL`, and the delete path always
  redirects to the feed.

## What went badly / what to change

- **Documentation lagged code by a full sprint.** SRS, diagrams and retro
  notes were left to the end of Sprint 2 instead of being written with each
  PR. Fix: from Sprint 3 on, each feature branch carries its docs increment
  (SRS section + mapping row) in the same PR.
- **No written record during the sprints.** Decisions were approved in chat
  on 2 Nov but only written into the sprint plan afterwards. Fix: this file
  and the plan are now the record; Sprint 3+ reviews are appended here, dated
  on the day they happen.
- **UI looked generic.** User feedback on the Sprint 2 preview: the interface
  looks AI-made. Decision: functionality ships as-is; a dedicated visual
  redesign pass (typography, spacing, colour system, hierarchy — no functional
  changes) runs with the Sprint 3 three-column layout work, using the
  frontend design skills already installed. New Sprint 3 UI should follow the
  existing style loosely and not over-invest in visuals the redesign will
  replace.
- **PHP availability is fragile.** PHP was installed in the workspace on
  2 Nov but did not survive the VM change, so this docs run reinstalled it
  before the test suites could run again. The suites themselves are unaffected
  (plain `php tests/*.php`, SQLite fallback), but the install step must be
  repeated whenever the workspace resets; XAMPP remains the fallback.

## Decisions carried forward (approved 2 Nov 2026)

1. Keep `Post`/`posts` naming; document the WBS `Tweet` mapping (done in
   `wbs-mapping.md`) instead of renaming.
2. Build a real DB-backed session system in a later sprint (sessions table:
   session id, user_id, expiry).
3. Compressed schedule holds: Sprint 3 on 5–8 Nov, Sprint 4 on 9–12 Nov,
   Sprint 5 on 13–15 Nov; if time runs short, polish is cut before core
   features.
4. Sprint 4 needs a real Redis server on the demo machine for the
   MySQL-vs-Redis benchmark; the app keeps its graceful MySQL fallback, so it
   runs without Redis, but the benchmark itself cannot be demoed without it.
5. Sprint 5 delivers a seed-data script and a deployment guide (fresh machine
   running in ~5 minutes).

## Sprint 2 close checklist

- [x] Backend merged (PR #1, `cc7c1eb`)
- [x] UI merged (PR #2, `f496a09`)
- [x] Tests green on merge day (UserTest 17/17, PostTest 29/29)
- [x] Browser flow + XSS check passed 2 Nov
- [ ] This docs increment reviewed and approved by the user (pending — the
      branch is **not** pushed or merged until approval)
