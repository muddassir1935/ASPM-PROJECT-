---
intent: 003-evaluation-fixes
phase: inception
status: draft
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:08:17Z
---

# Requirements: Sir's Evaluation Fixes (OOP + DB)

## Intent Overview
This intent fixes the exact deductions in Sir's (Muhammad Sudais) evaluation of the submitted CampusConnect project: **OOP 6/10 (lost 4)** and **DB 8.5/10 (lost 1.5)**. Sir gave the team an extended period to fix the lost-mark areas and will **re-evaluate on Tue 24 Nov 2026** (presentation + review the same day). Every deduction was verified against the repo before this intent was written — see the verification record in the planning pack. Work is decomposed into 10 focused units with Fibonacci story-point estimates and MoSCoW priorities. **No new features are introduced.** Total: Must + Should **39 points** for up to **5.5 marks**; the PHPUnit migration (13 points) is Could — only if time remains.

Source: Sir's evaluation message (photos, 18 Nov 2026). Full extraction + verification: `sir-evaluation-oop-db.md` (goal files). Planning pack: `planning-pack-extended-period.md` (goal files).

**Assignment:** units are unassigned for now — allocation is decided at the planning session. The intent is planning only at this stage.

## Business & Quality Goals

| Goal | Success Metric | Priority |
|------|----------------|----------|
| Real OOP design | Model classes share a base class; custom interfaces exist and are used | Must |
| Honest pattern claims | Every pattern named in the report is actually implemented | Must |
| Separation of concerns | Request handling lives in controllers, pages are thin entry points | Must |
| Constrained data | reports.reason/status constrained; notifications.reference_id protected | Must |
| Single schema source | SQLite schema generated from db/schema.sql, zero drift | Must |
| Zero regressions | All 412+ tests pass with new coverage for every fix | Must |

---

## Functional Requirements (Evaluation Fixes)

### FR-1: Base Model Class & Custom Interfaces (OOP-1, −2 marks)
- **Owner**: Unassigned (to be allocated at the planning session)
- **Description**: Introduce a `Model` base class holding the PDO connection and shared helpers (10 model classes repeat the same constructor shape today), plus custom interfaces — starting with `CacheDriverInterface` (get/set/delete) implemented by the Redis and in-memory drivers.
- **Priority**: Must
- **Estimate**: 8 story points
- **Assigned Unit**: `001-oop-foundation`

### FR-2: Real Chain of Responsibility for Cache Drivers (OOP-2, −0.5 marks)
- **Owner**: Unassigned (to be allocated at the planning session)
- **Description**: Replace the string-switched if/else backend pick in `Cache.php` with a real handler chain (phpredis → TinyRedisClient → memory), each handler trying its backend and passing on failure. Alternative (2 points, deliberate decision): remove the "Chain of Responsibility" claim from the report and describe the fallback accurately.
- **Priority**: Should
- **Estimate**: 3 story points
- **Assigned Unit**: `002-cache-chain`

### FR-3: Controllers for Auth + Profile Actions (OOP-3, −1 mark, part 1 of 3)
- **Owner**: Unassigned (to be allocated at the planning session)
- **Description**: Move login, registration, profile and password `*_action.php` flows into `AuthController` and `ProfileController`; pages become thin entry points.
- **Priority**: Must
- **Estimate**: 5 story points
- **Assigned Unit**: `003-controllers-auth`

### FR-4: Controllers for Post + Social Actions (OOP-3, part 2 of 3)
- **Owner**: Unassigned (to be allocated at the planning session)
- **Description**: Move post, like, follow, reply and bookmark actions into controllers (`PostController` exists; add `SocialController`); pages delegate. AJAX responses unchanged.
- **Priority**: Must
- **Estimate**: 5 story points
- **Assigned Unit**: `004-controllers-social`

### FR-5: Controllers for Admin + Moderation Actions (OOP-3, part 3 of 3)
- **Owner**: Unassigned (to be allocated at the planning session)
- **Description**: Move admin and report-review actions (role changes, deactivation, report review, password resets) into `AdminController`; `admin.php` / `report_review.php` delegate. RBAC stays enforced.
- **Priority**: Must
- **Estimate**: 3 story points
- **Assigned Unit**: `005-controllers-admin`

### FR-6: Static Helpers Become Injectable Objects (OOP-4, −0.5 marks, part 1 of 2)
- **Owner**: Unassigned (to be allocated at the planning session)
- **Description**: Convert Auth, Csrf, Authorization and SafeRedirect (7 + 5 + 6 + 3 static methods today) to instantiable classes with instance methods, passed in through constructors.
- **Priority**: Should
- **Estimate**: 5 story points
- **Assigned Unit**: `006-helpers-objects`

### FR-7: Migrate the Test Harness to PHPUnit (OOP-4, part 2 of 2)
- **Owner**: Unassigned (to be allocated at the planning session)
- **Description**: Migrate the 412-test suite from the home-made `run_all.php` + `assertTest` harness to PHPUnit with the same assertions and pass count. Largest estimate in the intent for a share of 0.5 marks — do only if all Musts and Shoulds are done with time to spare. If dropped, record it as an explicit decision in the retro.
- **Priority**: Could
- **Estimate**: 13 story points
- **Assigned Unit**: `007-phpunit-migration`

### FR-8: Constrain reports.reason + reports.status (DB-1, −0.5 marks)
- **Owner**: Unassigned (to be allocated at the planning session)
- **Description**: Add ENUM (or CHECK) constraints on `reports.reason` (VARCHAR(32), unconstrained) and `reports.status` (VARCHAR(16) DEFAULT 'pending', unconstrained) — MySQL migration + SQLite schema + tests.
- **Priority**: Must
- **Estimate**: 2 story points
- **Assigned Unit**: `008-db-constraints`

### FR-9: Foreign Key on notifications.reference_id (DB-2, −0.5 marks)
- **Owner**: Unassigned (to be allocated at the planning session)
- **Description**: Protect `notifications.reference_id` (no FK today) so a notification cannot point at a deleted post. **Design constraint:** the column is polymorphic — like/reply notifications hold a post id, but follow notifications hold a **user id** (`Follow.php:55`), so a plain FK to `posts(id)` would break follows. Scope the FK to post-type notifications (or split the reference per type); keep the existing read-time guard as defence in depth.
- **Priority**: Must
- **Estimate**: 3 story points
- **Assigned Unit**: `009-db-fk-reference`

### FR-10: Single Source of Truth for the SQLite Schema (DB-3, −0.5 marks)
- **Owner**: Unassigned (to be allocated at the planning session)
- **Description**: Generate the SQLite schema from `db/schema.sql` (translating MySQL-isms: AUTO_INCREMENT, ENGINE/CHARSET, TIMESTAMP defaults), replacing the 12 hand-written CREATE TABLEs in `config/Database.php` (~lines 61–180). Depends on FR-8/FR-9 so the generator emits the new constraints.
- **Priority**: Must
- **Estimate**: 5 story points
- **Assigned Unit**: `010-schema-generator`
