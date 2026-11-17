# Coding Standards — CampusConnect

## Language & style

- PHP 8, OOP throughout. One class per file in `classes/`, `PascalCase` class
  names, `camelCase` methods, `snake_case` DB columns.
- Pages at the repo root (`index.php`, `profile.php`, ...); `*_action.php` files
  are AJAX endpoints, not pages.
- Keep it small: the smallest change that solves the problem, no unrelated
  refactors, no new dependencies without telling the user.

## Database

- PDO prepared statements everywhere — never interpolate user input into SQL.
- All queries go through `config/Database.php` / the domain classes; no ad-hoc
  SQL in page scripts.
- Schema changes ship as a migration in `db/` AND a `db/schema.sql` update, so a
  fresh install and an upgraded install end up identical.

## Security (non-negotiable)

- `password_hash()` / `password_verify()` for passwords. Never store or log
  plaintext passwords.
- `htmlspecialchars()` on every user-controlled value at output.
- CSRF token on every state-changing form and AJAX call.
- Permission checks via `Auth::can()` / `Authorization::can()` — never compare
  roles directly in page code. The permission map is the single source of truth
  (`classes/Authorization.php`: 3 roles, 14 permissions).
- Login rate limit: 5 attempts per 15 minutes (`login_attempts` table).
- Session: 30-minute idle timeout; role changes/deactivation revoke sessions.
- Never weaken `.htaccess` / `router.php` protections on `db/`, `tests/`,
  `config/`, `classes/`, `docs/`. Never commit secrets.

## Testing

- Every new behaviour gets tests in `tests/`. Testable classes: inject the PDO
  instance, avoid static global state.
- Full suite: `php tests/run_all.php` (412 tests: 398 on throwaway SQLite + 14
  MySQL, skipped when MySQL is down). The suite must stay green before any
  stage is marked complete.
- Individual suites: `php tests/UserTest.php`, etc.

## Git

- Feature branches `feature/sprint-<n>-<name>`; no direct commits to `main`.
- One branch per feature — iterate in the same branch.
- Commit messages: short imperative summary, conventional-ish prefixes
  (`feat:`, `fix:`, `docs:`, `test:`) where they fit.
- Never push, merge, or tag without the user's explicit approval.
