# CampusConnect (ASPM PROJECT)

A centralized University Microblogging & Announcement Hub built using PHP (OOP) and MySQL.

## Agile Workflow Rules
- **No direct commits to `main`**. All development must happen on feature branches.
- Use descriptive branch names: `feature/<sprint-num>-<feature-name>` (e.g., `feature/sprint-1-auth-oop`).
- All code must follow Object-Oriented Programming (OOP) principles.
- Code should be testable (Unit testing).

## Setup Instructions
1. Import `db/schema.sql` into MySQL.
2. Update database credentials in `config/Database.php`.
3. Serve via XAMPP/Apache (PHP 8+).

## Documentation
- `docs/sprint-2-srs-increment.md`, `docs/sprint-3-srs-increment.md`, `docs/sprint-4-srs-increment.md`, `docs/sprint-5-srs-increment.md` — per-sprint requirements, as built.
- `docs/uml-class-diagram-erd.md` — Mermaid class diagram + ERD (Sprints 1–5).
- `docs/wbs-mapping.md` — WBS Tweet/Post naming map and coverage table.
- `docs/sprint-1-2-review-retro.md`, `docs/sprint-3-review-retro.md`, `docs/sprint-4-review-retro.md`, `docs/sprint-5-review-retro.md` — per-sprint review and retro notes.
- `docs/USER_MANUAL.md`, `docs/ADMIN_MANUAL.md` — end-user and admin guides.
- `docs/DEPLOYMENT.md` — 5-minute XAMPP quick start + seed accounts.
- `docs/DEMO_SCRIPT.md` — step-by-step live demo script for the review.
- `docs/CONTRIBUTION.md`, `docs/MEETINGS.md` — contribution register and meeting log.

Run the full test suite with plain PHP (SQLite fallback, no server needed):
`php tests/run_all.php` (286 tests). Individual suites live in `tests/` (e.g. `php tests/UserTest.php`).