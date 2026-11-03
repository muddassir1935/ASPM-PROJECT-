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
- `docs/sprint-2-srs-increment.md` — Sprint 2 requirements, as built.
- `docs/uml-class-diagram-erd.md` — Mermaid class diagram + ERD (Sprints 1–2).
- `docs/wbs-mapping.md` — WBS Tweet/Post naming map and coverage table.
- `docs/sprint-1-2-review-retro.md` — Sprint 1–2 review and retro notes.

Run the test suites with plain PHP (SQLite fallback, no server needed):
`php tests/UserTest.php` and `php tests/PostTest.php`.