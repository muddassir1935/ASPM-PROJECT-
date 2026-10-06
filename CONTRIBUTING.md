# Contributing Guidelines

## Agile Branching Strategy
1. **Never commit to `main` directly.** The `main` branch is for stable, reviewed code only.
2. Before starting a task from the WBS, create a branch using the format: `feature/sprint-<sprint_number>-<feature_name>`
   - Example: `feature/sprint-1-auth-oop`
3. Commit messages should be descriptive and reference the WBS item if applicable.

## Coding Standards
1. Use PHP OOP principles for all core logic (no procedural spaghetti).
2. Database interactions must use PDO with prepared statements.
3. Passwords must be hashed using `password_hash()`.
4. Include DocBlocks for classes and complex functions to ensure code is self-documenting.