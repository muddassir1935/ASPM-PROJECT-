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