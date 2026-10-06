# Agent Context for CampusConnect

This is an Agile project built with PHP and MySQL.
As an AI agent working on this repo, adhere to these rules:

1. **Agile Workflow**: Create feature branches (`feature/<sprint>-<feature-name>`) for development. Do not push directly to main without user consent.
2. **Architecture**: Follow OOP principles. Database logic goes into `config/Database.php` and specific domain logic goes into `classes/` (e.g., `User.php`).
3. **Security**: Ensure prepared statements (PDO) are used everywhere to prevent SQL injection. Passwords must use `password_hash()`.
4. **Testing**: The project requires unit testing readiness. Design classes so they can easily be tested with PHPUnit or basic assertion scripts.