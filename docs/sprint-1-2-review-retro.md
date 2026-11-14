# Sprint Review and Retro Notes (Sprints 1–5)

> Honesty note: no separate standup/meeting minutes were kept during the
> sprints, so none are invented here. This record is reconstructed from the
> git history, the merged pull requests, and the approved sprint plan, and is
> dated by the evidence, not backdated. Dates below are commit/merge dates.
> Per the Sprint 2 decision, Sprint 3+ reviews are appended to this same
> file (the filename keeps the original Sprint 1–2 name for stable links).

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

---

## Sprint 3 review — Social, Profiles, Discovery, Sessions, Design (WBS 3.0)

- **Delivered in three PRs, all merged 5 Nov 2026** (squash commits in
  `main`; `main` now `7fe4dda`):
  - PR #4 (merge `36315f9`): core backend — `Follow`, `Reply`, `Bookmark`
    models, personalized feed, search, DB-backed sessions
    (commit `6cbe7d4`, 13 files, +1,221).
  - PR #5 (merge `46b679c`): UI — profile page + edit, follower modals,
    explore + who-to-follow widget, threaded replies, bookmarks UI,
    3-column layout shell (commit `0c09dab`, 14 files, +1,222).
  - PR #6 (merge `7fe4dda`): design — product-grade redesign, character
    pass, per-user cover styles, Interlocked CC logo (commits `5572167` +
    `9bbf23e`).
- **Verified 5 Nov 2026:** tests **105/105** on a fresh database
  (UserTest 18/18, PostTest 29/29, SocialTest 49/49, SessionTest 9/9);
  a full live preview (login → feed → profile → explore → bookmarks →
  post detail, plus mobile) with demo data, and the cover-style flow
  end-to-end (select → save → reload persists). The personalized feed was
  shown working: only the viewer's + followed users' posts in the feed,
  an unfollowed user's post visible on explore only.
- **Scope note:** the design work took three iterations in one day, all on
  the same branch by the end (see "What went badly" below). The shipped
  Sprint 3 is the v2 restraint direction + character pass + cover styles +
  user-chosen logo — not the first "Campus masthead" draft, which the user
  rejected as still generic-AI. The Sprint 3 window runs to 8 Nov; only
  this documentation increment remained at merge time, which is what the
  `feature/sprint-3-docs` branch closes.

## What went well (Sprint 3)

- Small, reviewable PRs again: core first, UI second, design third — each
  verified before the next started, and nothing pushed until the user
  approved the whole stack together (his call on 5 Nov: "sab kuch
  redesign ke baad ek saath push karna").
- Verification caught a real bug the unit tests could not: the wrong
  `Follow.php` path in `social_widgets.php` truncated four pages at the
  sidebar. It was fixed, live-render verified, and the fix is in `main`.
- The user's design feedback loop worked: each rejection named the real
  problem ("AI feel", "pheeka pheeka", "logo too simple"), and each pass
  answered it with a specific change rather than a re-skin — restraint
  first, then character, then a logo choice presented as three testable
  concepts at 72/28/16px.

## What went badly / what to change (Sprint 3)

- **The Sprint 3 job's own instruction contradicted the approved plan.**
  Problem 7 approved a full design system in Sprint 3, but the job told
  the worker "functionality first, visuals per zyada invest na karna", so
  the first UI shipped with the old generic style. The user had to catch
  it in preview. Fix: sprint-start jobs must quote the approved plan's
  scope instead of paraphrasing it; any "don't invest in X" line needs the
  plan reference it is overriding.
- **A new branch per UI iteration.** The redesign was started on
  `feature/sprint-3-design`, then a `feature/sprint-3-design-v2` was
  created for the second pass. The user corrected this on 5 Nov: one
  feature, one branch, iterations in place. The v2 work was fast-forwarded
  into the design branch and the v2 branch deleted; this is now a standing
  branch-discipline rule for both projects.
- **Docs lagged code again** — the Sprint 2 retro's fix ("each feature
  branch carries its docs increment in the same PR") did not happen for
  Sprint 3; the docs are a separate branch after the merge, as in Sprint 2.
  Fix: Sprint 4 and 5 branches must include their SRS section + mapping
  row before the PR is opened, not after it merges.
- **PHP availability is still fragile.** PHP had to be reinstalled in the
  workspace twice on 5 Nov (apt locks, then a VM change wiping the
  install), which delayed the live preview. The suites still run on plain
  `php tests/*.php` with the SQLite fallback; XAMPP remains the fallback.
  This docs run could not re-run the suites at all (no PHP in the
  workspace) — see the Sprint 3 SRS increment §6.

## Decisions carried forward (approved 5 Nov 2026)

1. Cover styles, not photo upload, for profile covers (6 whitelisted
   styles; server-side validation; no new upload surface). Photo upload is
   a later-sprint option only if the user asks.
2. Logo is Interlocked CC (user's Concept 1 choice, over the assistant's
   Nodes C recommendation). Brand character stays spent in only two
   places: the auth brand panel and the profile cover band.
3. Known minor gaps accepted for Sprint 3: compose box fixed 58px (no
   auto-grow) and bookmark saved-state shown by word only. Small JS
   follow-ups, not Sprint 4 scope unless the user asks.
4. `SocialTest` flakiness on a crowded test database (48/49) goes on the
   Sprint 5 test-database cleanup list.
5. Compressed schedule holds: Sprint 4 on 9–12 Nov (likes, hashtags,
   Redis caching), Sprint 5 on 13–15 Nov (notifications, hardening, final
   delivery). Sprint 4 still needs a real Redis server on the demo machine
   for the MySQL-vs-Redis benchmark; the app keeps its graceful MySQL
   fallback.

## Sprint 3 close checklist

- [x] Core merged (PR #4, `36315f9`)
- [x] UI merged (PR #5, `46b679c`)
- [x] Design merged (PR #6, `7fe4dda`)
- [x] Tests green on merge day (105/105 on a fresh database)
- [x] Live preview + cover-style flow verified 5 Nov
- [x] User runs `db/migrate_sprint3_mysql.sql` then
      `db/migrate_cover_style_mysql.sql` on XAMPP MySQL before demoing
- [x] This docs increment reviewed and approved by the user (merged `4076dda`)

## Sprint 4 review — Engagement, Hashtags & Caching (WBS 4.0)

- **Delivered (9 Nov 2026):**
  - **Likes & Heart Reactions (WBS 4.1):** `likes` schema with cascade delete, `Like` domain model with idempotent operations, AJAX endpoint `like_action.php`, heart SVG with real-time DOM counter updates, and Profile "Likes" tab (`profile.php`).
  - **Hashtags & Discovery (WBS 4.2):** `hashtags` and `post_hashtags` schemas, automatic extraction and indexing upon create/update in `Hashtag` class, clickable `#tag` link rendering in posts, tag filter exploration (`explore.php?tag=...`), and "Trending Topics" right sidebar widget.
  - **Performance Caching & Benchmark (WBS 4.3):** `Cache` Singleton supporting Redis with zero-downtime memory fallback, intelligent `feed_` prefix cache invalidation on post/like mutation, and `benchmark.php` benchmark suite.
  - **Sprint 3 Polish Items Closed:** Compose box auto-grow on multi-line text and filled bookmark icon SVG when saved.
  - **Automated Tests:** Comprehensive `tests/Sprint4Test.php` suite and
    regression validation across all project suites (see verification note
    below — the count moved during the verification pass).
- **Verified 9 Nov 2026:** Faiz merged Sprint 4 himself (`d7f1938`)
  reporting 140/141 (1 pre-existing flaky test). An independent
  verification pass the same day found **3 real bugs** — including a
  critical one: the hashtag renderer linkified `#039` inside the `&#039;`
  HTML entity, breaking every post containing an apostrophe — plus a
  profile Likes-tab viewer-state bug and a bookmarks like-count bug. The
  fixes (PR #8, main `d1e4400`) also added `TinyRedisClient` (pure-PHP
  RESP client, so the demo needs only a Redis server, not the phpredis
  extension), the feed-cache TTL/invalidation fixes, the N+1 audit, and
  regression tests. **Suite after the verification pass: 153/153 green.**
  Cache benchmark: 532× measured memory-read speedup over direct database
  queries (Sprint 4 verification, memory fallback backend).

## Sprint 4 retro notes

- **What went well:**
  - Single feature branch discipline maintained (`feature/sprint-4-core`), preventing branch explosion.
  - Docs (`sprint-4-srs-increment.md`, WBS mapping, UML/ERD updates) written concurrently with code in the same branch, fixing the retro gap from Sprints 2 & 3.
  - The verification pass earned its keep: a merge the author believed green
    (140/141) actually shipped a critical rendering bug. "Verified" now
    means an independent run of the full suite + a live walkthrough, not
    the author's own count.
- **What went badly / what to change:**
  - **Claimed numbers were not the real numbers.** The 140/141 figure was
    quoted in docs before anyone re-ran the suite; the true post-fix count
    was 153/153. Fix: docs quote only numbers from a run somebody
    re-performed and can point at (this file now does).
  - **The flaky test was never chased to root cause** (it was the known
    SocialTest-on-crowded-DB flake from Sprint 3). It disappeared with the
    Sprint 5 test-database isolation work instead of being fixed directly.
    Lesson: a flaky test is a bug report, not background noise.
- **Sprint 4 Close Checklist:**
  - [x] Likes system functional and tested (WBS 4.1)
  - [x] Hashtags and trending topics functional and tested (WBS 4.2)
  - [x] Redis caching layer with seamless memory fallback and benchmark (WBS 4.3)
  - [x] 153/153 unit tests green after the verification pass (PR #8, `d1e4400`)
  - [x] Documentation, SRS increment, WBS mapping, and UML/ERD synchronized
  - [ ] User runs `db/schema.sql` on XAMPP MySQL before demoing (XAMPP not set up yet — fresh setup is schema.sql only)
  - [x] Push to remote and merge `feature/sprint-4-core` into `main` after user consent (Merged `d7f1938`)

---

## Sprint 5 review — Final Delivery, Hardening, Polish & Academic Release (WBS 5.0)

- **Delivered (12–14 Nov 2026; release tag 15 Nov):**
  - **In-App Activity Notifications (WBS 5.1, Faiz):** `notifications`
    table, `Notification` model, unread badge in the header, dedicated
    center (`notifications.php`), triggers on follow/like/reply. PR #9,
    merged 13 Nov (main `8f516cf`); 4 review fixes applied on Faiz's
    branch first (missing table in `schema.sql`, auth order, `create()`
    error handling, `rowCount()` truth). Suite at merge: 181/181.
  - **Role-Based Access Control & Society Management (WBS 5.2,
    Muddassir & Muse):** roles `student` / `society_admin` /
    `system_admin`, central `Authorization` permission map, admin panel
    with role assignment (`admin.php`), society announcements
    (`posts.is_announcement`), 30-min idle timeout, session revocation.
    PR #11, merged 13 Nov (main `2c92121`). Suite at merge: 274/274
    (incl. 91 `RbacTest`).
  - **Security Hardening (WBS 5.3, Muddassir & Muse):** CSRF tokens on
    all state-changing forms + AJAX, login rate-limit (5 attempts / 15
    min), `.htaccess` blocking, visible MySQL→SQLite fallback banner,
    test-DB isolation + demo re-seed, hidden public emails + name-only
    search, hardened session cookies + security headers. Same PR #11.
    The full security audit (12 Nov) found 0 Critical, 7 High, 12 Medium
    — all 7 High fixed in this sprint.
  - **Responsive Polish & Micro-interactions (WBS 5.4, Qaim + polish
    pass):** Qaim's PR #10 (merged 13 Nov, main `58e1ece`) fixed the
    mobile Explore clipping and 5px feed scroll and added button-press
    + heart-pop micro-interactions. The 14 Nov UI polish pass
    (`feature/sprint-5-ui-polish`) then closed all 20 must-fix items
    from the 12 Nov UI/UX audit: post-detail 3-column shell, relative
    timestamps, pluralization, unified owner actions, Escape closes
    modals, rebuilt benchmark page, Explore pagination, 44px touch
    targets, aria-labels, auth watermark, empty-state alignment,
    who-to-follow truncation, and the approved Bricolage Grotesque +
    Public Sans fonts. Suite: 286/286; live walkthroughs 39/39 +
    14/14. Password management added the same day on the same branch
    (change own password + admin reset with a one-time temporary
    password), live-verified 13/13.
  - **Seed Dataset & 5-Minute Deployment (WBS 5.5, Faiz):** `db/seed.sql`
    (7 users / 8 posts / 10 follows / 6 replies / 11 likes) +
    `docs/DEPLOYMENT.md` quick start. Demo DB re-seeded clean on 13 Nov
    (was 293 users / 256 posts of test junk).
  - **Academic Documentation & Release Deliverables (WBS 5.6 & 5.7,
    Qaim + team):** `docs/sprint-5-srs-increment.md`, `USER_MANUAL.md`,
    `ADMIN_MANUAL.md`, `CONTRIBUTION.md`, `MEETINGS.md`,
    `docs/DEMO_SCRIPT.md`, this retro file. Deck + report
    (`AIR_Presentation.pptx`, `AIR_Report.pdf`) regenerate from merged
    main on Sat evening (after the polish merge) so the numbers are
    final; the working-software zip regenerates from merged main on
    15 Nov via `scripts/package_release.py`.
- **Verified 14 Nov 2026:** full suite **286/286** on the polish branch;
  live walkthroughs green (39/39 polish + 14/14 cut-items + final-fix
  checks); demo DB pristine (7 users / 8 posts / 6 replies / 11 likes /
  10 follows). A final gap sweep on 14 Nov (audits + sprint plan + docs
  re-checked line by line) found and fixed 13 more items before the
  merge — a real N+1 in the profile modals, missing register validation,
  double-submit duplicates, missing transactions, the benchmark page
  being both public and `.htaccess`-blocked, and stale "in progress"
  claims across five docs.
- **Scope note:** Sprint 5 was re-scoped on 12 Nov for fairness — the
  user had built nearly everything alone (26 commits vs 4 vs 0 at the
  time), so notifications + docs/deliverables went to Faiz and Qaim as
  their own marked work, while Muddassir + Muse did RBAC + audit fixes
  and verified/validated the teammates' PRs instead of redoing them.
  Final commit counts are recomputed after the polish merge (the report
  generator's literals are updated in the same regen step).

## Sprint 5 retro notes

- **What went well:**
  - **Equitable work distribution, actually enforced.** The 12 Nov split
    gave Faiz and Qaim real ownership with commits under their own names
    (Faiz: notifications + seed + deployment guide; Qaim: responsive
    polish + docs + deck/report). Both PRs were reviewed and fixed on
    their own branches, not rewritten by someone else.
  - **Verification as a habit, not a phase.** Every merge in Sprint 5 was
    preceded by an independent suite run + live walkthrough, and the
    habit paid off three times: PR #9's missing table, PR #10's ten
    blocking doc errors, and the final-day sweep's 13 gaps were all
    caught before they reached main.
  - **Audits before the last weekend.** Running the security + UI audits
    on 12 Nov (not 14 Nov) left real time to fix what they found —
    including the RBAC work the user himself raised (roles were only a
    display label; `society` was never checked; signup always made a
    student).
- **What went badly / lessons learned:**
  - **Docs lagged code for the fourth sprint in a row.** The Sprint 5
    docs were written while RBAC was still on its branch, so they
    described merged work as "in progress" — and the deck/report numbers
    went stale twice (after PR #10, then again after PR #11). The fix
    applied on 14 Nov: docs describe only merged main, and generated
    deliverables regenerate last, from merged main. Lesson: a doc that
    describes a branch is a draft, not a deliverable.
  - **A notification is not proof of delivery.** On 13 Nov the team
    believed a GitHub email meant the post-RBAC deck/report regen was
    done; checking branches, open PRs and main showed it was the earlier
    pre-RBAC binary step. Lesson: verify remote state, not inboxes.
  - **Early contribution asymmetry.** Sprints 1–3 were delivered with
    insufficient commit distribution. Sprint 5 required a deliberate
    restructuring to ensure all team members contributed commits for
    academic credit. Lesson: enforce team pair-programming and
    distributed pull requests from Sprint 1 in future projects.
  - **Toolchain fragility, finally solved properly.** PHP and OpenMPI
    installs kept dying on VM replacements until the toolchains were
    moved into the persistent workspace (`~/workspace/tools/`) with
    wrappers and cached `.deb`s — a written rule cannot protect what
    the infrastructure wipes; the fix has to live where the wipe cannot
    reach.
- **Sprint 5 Close Checklist:**
  - [x] Notifications merged (PR #9, `8f516cf`)
  - [x] Docs + responsive polish merged (PR #10, `58e1ece`)
  - [x] RBAC + security top-5 merged (PR #11, `2c92121`)
  - [x] Security audit 0 Critical; all 7 High fixed
  - [x] UI audit: all 20 must-fix closed (polish branch)
  - [x] Full suite 286/286 + live walkthroughs green (14 Nov)
  - [x] Demo DB re-seeded clean (7 users / 8 posts)
  - [x] Demo script written (`docs/DEMO_SCRIPT.md`)
  - [x] GroupCode confirmed by user: Section `BCS-7A`, Group Code `AIR`
  - [ ] UI polish branch merged to main (pending user push approval, 14 Nov)
  - [ ] Deck + report regen from merged main (Sat evening, after the polish merge)
  - [ ] Working-software zip from merged main (15 Nov)
  - [ ] Release tag (15 Nov, needs the user's explicit approval)
  - [ ] XAMPP/MySQL setup + `.htaccess` check on the user's machine (first-time setup)
