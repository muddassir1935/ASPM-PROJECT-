# CampusConnect (ASPM PROJECT)

A centralized University Microblogging & Announcement Hub built using PHP (OOP) and MySQL.

## Agile Workflow Rules
- **No direct commits to `main`**. All development must happen on feature branches.
- Use descriptive branch names: `feature/<sprint-num>-<feature-name>` (e.g., `feature/sprint-1-auth-oop`).
- All code must follow Object-Oriented Programming (OOP) principles.
- Code should be testable (Unit testing).

## AI-DLC (specs.md)
This repo runs the AI-Driven Development Lifecycle via [specs.md](https://github.com/fabriqaai/specs.md) (AI-DLC flow, Google Antigravity tooling): agents in `.specsmd/aidlc/agents/`, skills in `.specsmd/aidlc/skills/`, project context in `memory-bank/`. In Antigravity, start with `/specsmd-master-agent` (workflows in `.agent/workflows/`). Agent instructions: `AGENTS.md`. Quick start: `.specsmd/aidlc/quick-start.md`.

## Setup Instructions
1. Import `db/schema.sql` into MySQL, then `db/seed.sql` for the demo accounts (password `StrongPass123!`).
2. Update database credentials in `config/Database.php`.
3. Serve via XAMPP/Apache (PHP 8+), or with PHP's built-in server: `php -S localhost:8000 router.php` (always pass `router.php` — it blocks internal files the way `.htaccess` does on Apache).

Full guide: `docs/DEPLOYMENT.md`.

## Documentation
- `docs/sprint-2-srs-increment.md`, `docs/sprint-3-srs-increment.md`, `docs/sprint-4-srs-increment.md`, `docs/sprint-5-srs-increment.md` — per-sprint requirements, as built.
- `docs/uml-class-diagram-erd.md` — Mermaid class diagram + ERD (Sprints 1–5).
- `docs/wbs-mapping.md` — WBS Tweet/Post naming map and coverage table.
- `docs/sprint-1-2-review-retro.md` — review and retro notes for **all five sprints** (Sprints 3–5 are appended to this one file; the filename is kept for stable links).
- `docs/USER_MANUAL.md`, `docs/ADMIN_MANUAL.md` — end-user and admin guides.
- `docs/DEPLOYMENT.md` — 5-minute XAMPP quick start + seed accounts.
- `docs/DEMO_SCRIPT.md` — step-by-step live demo script for the review.
- `docs/CONTRIBUTION.md`, `docs/MEETINGS.md` — contribution register and meeting log.
- `docs/AUDIT_FINDINGS.md` — 15 Nov code/feature/documentation audit: every finding, its outcome and the fixing commit.

Run the full test suite with plain PHP (SQLite fallback, no server needed):
`php tests/run_all.php` (412 tests: 398 on throwaway SQLite + 14 against a temporary MySQL database, skipped when MySQL is down). Individual suites live in `tests/` (e.g. `php tests/UserTest.php`).