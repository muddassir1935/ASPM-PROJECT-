# Units — Intent 001: CampusConnect

Units here are the five sprints plus the two cross-cutting workstreams, all
**completed**. This is a brownfield record: no retroactive bolts or stories
were created for finished work. Per-unit detail lives in `docs/` (SRS
increments, review/retro notes, audits).

| # | Unit | Status | Built by | Key PRs |
|---|---|---|---|---|
| 001 | Sprint 1 — Auth (register/login/sessions) | completed | Muddassir | pre-PR history |
| 002 | Sprint 2 — Post engine + feed (chirps, replies, follows) | completed | Muddassir | #1–#3 |
| 003 | Sprint 3 — Social + UI + docs (likes, bookmarks, profiles, design) | completed | Muddassir | #4–#7 |
| 004 | Sprint 4 — Likes / hashtags / Redis cache | completed | Faiz (+ Muse bugfixes) | #8 (3 fixes incl. CRITICAL apostrophe bug) |
| 005 | Sprint 5 — Notifications, responsive polish, SRS final, manuals, report, deck | completed | Faiz + Qaim | #9 (notifications), #10 (docs + responsive) |
| 006 | RBAC + security hardening (roles, permissions, CSRF, rate limit, audit top-5) | completed | Muddassir + Muse | #11 |
| 007 | Password management + X-style redesign + reporting/moderation | completed | Muddassir, Faiz, Qaim | #12 (passwords), #13 (redesign + campus email guard), #14 (Faiz: wider feed/user-dock/delete modal), #15 (Qaim: reporting + moderation queue) |

## Final state (verified 2026-11-17, main `a3e1232`)

- 78 commits: Muddassir 46, Faiz 13, Qaim 12, Haseeb Asif 7 (Haseeb is not in
  Group AIR — his 7 commits are a post-submission audit pass, 15 Nov 23:36).
- 412 tests (398 SQLite + 14 MySQL), full suite green.
- 12 tables, 3 roles, 14 permissions.
- Submitted 16 Nov 2026 from `424390e` (322 tests, 71 commits) — the team's
  own frozen build. Main has moved on since (`a3e1232`); the submission is
  the frozen snapshot, not current main.

## Open items (not new units — Operations backlog)

- Release tag — needs the user's explicit approval.
- 17 remote non-main branches, all verified fully merged — deletion pending
  on the user.
- UI polish items cut for time: M-9, M-12, M-13, M-14, M-16, M-17 (see
  `requirements.md`).
