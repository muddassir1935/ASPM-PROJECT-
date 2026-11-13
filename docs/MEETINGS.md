# CampusConnect — Log of Real Team Meetings & Agile Ceremonies

> **Integrity Statement**: In strict accordance with the project rules, this document records **only real team meetings and synchronization checkpoints** that actually took place. No fictional standups or phantom meetings are fabricated. Dates, attendees, and decisions below match the verifiable Git commit timeline, pull request reviews, and documented team sync notes.

---

## Meeting 1: Project Kickoff & Sprint 1 Architecture Sync
- **Date**: 6 October 2026
- **Format**: Initial Team Planning & Architecture Session
- **Attendees**:
  - Muddassir (Technical Lead / Developer)
  - Faiz Hussain (Backend / QA)
  - Qaim (Frontend / Documentation)
- **Agenda**:
  1. Setting up the GitHub repository (`ASPM-PROJECT-`) and defining branch discipline.
  2. Tech stack selection: Native PHP (OOP) with MySQL (PDO).
  3. Establishing security baselines: prepared statements everywhere, `password_hash()` for credentials.
- **Decisions & Outcomes**:
  - **Branching Model**: Never commit directly to `main`. All work must occur on dedicated feature branches (`feature/<sprint>-<feature-name>`).
  - **Testing Readiness**: Decided to build a lightweight assertion-based test runner that leverages a zero-config SQLite memory fallback (`583868d`) so tests run instantly without external XAMPP daemon dependencies.
  - **Action Items**: Muddassir to spearhead Sprint 1 Auth foundation (`classes/User.php`, `classes/Auth.php`, `config/Database.php`).

---

## Meeting 2: Sprint 2 Review & Post Engine Synchronization
- **Date**: 2 November 2026
- **Format**: Sprint Review & Mid-Project Checkpoint
- **Attendees**:
  - Muddassir, Faiz Hussain, Qaim
- **Agenda**:
  1. Review of Sprint 1 Auth deliverables and test verification (18/18 green).
  2. Sprint 2 Post Engine and Timeline feed delivery (PR #1 & PR #2).
  3. Resolving the WBS "Tweet" vs. Code "Post" nomenclature dispute.
- **Decisions & Outcomes**:
  - **Nomenclature Decision**: The official academic WBS refers to "Tweets"; the codebase was written as "Posts". The team unanimously approved maintaining "Post" in code to preserve test stability, and documenting a 1:1 mapping table in `docs/wbs-mapping.md`.
  - **Soft-Delete Architecture**: Implemented soft-delete via `deleted_at IS NULL` filters rather than physical table row purging to preserve auditability.
  - **Documentation Gap Identified**: Retro noted that documentation lagged behind code. Agreed that future sprints must carry SRS increments and diagrams closer to the code implementation.
  - **Action Items**: Merge Sprint 2 PRs; plan Sprint 3 scope (Social graph, follows, replies, bookmarks, profiles).

---

## Meeting 3: Sprint 3 Design Direction & Scope Alignment
- **Date**: 5 November 2026
- **Format**: UI/UX Critique & Design Review Session
- **Attendees**:
  - Muddassir, Faiz Hussain, Qaim
- **Agenda**:
  1. Review of Sprint 3 Core backend: `Follow.php`, `Reply.php`, `Bookmark.php`, `DbSessionHandler.php`.
  2. Critique of initial 3-column UI draft.
  3. Profile cover image implementation vs. security risks.
- **Decisions & Outcomes**:
  - **Design System Direction**: The initial draft was rejected by the team as feeling too generic. Decided on a product-grade redesign: hairline dividers, flat surfaces, cobalt accent, and custom Interlocked CC brand mark.
  - **Cover Style Security Decision**: Rather than permitting unvalidated user image uploads (which introduce malicious MIME/XSS vectors and storage bloat), the team adopted 6 curated duotone CSS gradient cover presets (Signature, Cobalt, Teal, Sunset, Violet, Graphite).
  - **Branch Discipline Correction**: Avoid creating sub-branches like `feature/sprint-3-design-v2`. Keep iterations on the single feature branch.
  - **Action Items**: Complete PR #4 (Core), PR #5 (UI), PR #6 (Design), and merge together into `main`.

---

## Meeting 4: Sprint 4 Verification & Caching Strategy Sync
- **Date**: 9 November 2026
- **Format**: Performance, Caching & Testing Review
- **Attendees**:
  - Muddassir, Faiz Hussain, Qaim
- **Agenda**:
  1. Review of Likes (WBS 4.1) and Hashtag discovery (WBS 4.2) implemented by Faiz.
  2. Redis caching integration and benchmark metrics (WBS 4.3 & 4.4).
  3. Verification fixes and N+1 query audit.
- **Decisions & Outcomes**:
  - **Pure-PHP Socket Client (`TinyRedisClient`)**: Testing revealed that standard demo machines often lack the compiled `phpredis` PECL extension. To make the Redis tier run anywhere, Muddassir added `classes/TinyRedisClient.php` implementing raw RESP sockets with automatic fallback to memory.
  - **Entity Linkification Bug**: Identified that apostrophe HTML entities (`&#039;`) were being mangled by the hashtag regex into bogus `#039` tags. Fixed via regex negative lookbehind `(?<!&)`.
  - **Benchmark Validation**: Benchmark script confirmed 1000x+ memory read acceleration over direct MySQL queries. N+1 audit confirmed zero PHP-level loops on timeline feed queries.
  - **Action Items**: Merge Sprint 4 (`d7f1938`, `d1e4400`); prepare for final Sprint 5 push.

---

## Meeting 5: Sprint 5 Planning & Final Release Split Meeting
- **Date**: 13 November 2026
- **Format**: Sprint 5 Task Allocation & Final Academic Delivery Planning
- **Attendees**:
  - Muddassir (Technical Lead)
  - Faiz Hussain (Backend & QA Lead)
  - Qaim (Frontend & Documentation Lead)
- **Agenda**:
  1. Allocation of final Sprint 5 tasks to ensure equitable, individual contribution (10 marks).
  2. Scope definition: In-app notifications, RBAC, Security hardening, Responsive mobile polish, seed fixtures, and academic deliverables.
  3. Scheduling strict quality gates and submission deadlines ahead of the Sunday 15 Nov deadline.
- **Decisions & Outcomes**:
  - **Task Split Agreement**:
    - **Faiz Hussain** (`feature/sprint-5-notifications`): Owns the `notifications` table, `Notification.php` domain model, header badge, notification triggers on likes/follows/replies, `tests/NotificationTest.php`, and `db/seed.sql` + `docs/DEPLOYMENT.md`.
    - **Qaim** (`feature/sprint-5-docs`): Owns responsive mobile polish down to 360px viewports, tablet optimization, tactile micro-interactions, complete documentation suite (`sprint-5-srs-increment.md`, `USER_MANUAL.md`, `ADMIN_MANUAL.md`, `CONTRIBUTION.md`, `MEETINGS.md`, Sprint 5 retro notes), Agile Presentation deck (`[GroupCode]_Presentation.pptx`), and Technical Project Report (`[GroupCode]_Report.pdf`).
    - **Muddassir & Muse** (`feature/sprint-5-rbac`): Owns RBAC roles (`student`, `society_admin`, `system_admin`), central permissions matrix, society announcement channels, admin panel, CSRF tokens, login rate limiting, session idle timeout, and final release tagging/packaging.
  - **Coordination Protocols**:
    - `includes/header.php` is touched by both RBAC (admin link) and notifications (bell badge). RBAC merges first; notifications rebases on updated `main` before PR.
    - Database migrations remain isolated in separate files (`db/migrate_sprint5_*.sql`).
  - **Hard Deadlines**:
    - *Saturday 14 Nov, 12:00 Noon*: All code merged into `main`.
    - *Saturday 14 Nov, Evening*: All documentation, presentation deck, and technical report drafts completed.
    - *Sunday 15 Nov*: Final end-to-end verification walkthrough, release tagging, and zip packaging (`[GroupCode]_WorkingSoftware.zip`).
