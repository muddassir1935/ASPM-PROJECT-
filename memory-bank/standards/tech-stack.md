# Tech Stack — CampusConnect

## Runtime

| Layer | Choice |
|---|---|
| Language | PHP 8 (developed/tested on 8.3; code targets 8.1+) |
| Framework | None — plain PHP, OOP, no build step |
| Web server | Apache via XAMPP (Windows demo) or PHP built-in server with `router.php` |
| Database | MySQL / MariaDB via PDO; SQLite fallback when MySQL is unreachable (loud banner, never silent) |
| Cache | Redis when available (default `127.0.0.1:6379`); in-memory fallback otherwise |
| Frontend | Vanilla HTML/CSS/JS, no bundler, no CSS framework |
| Fonts | Google Fonts: Inter (UI), Bricolage Grotesque (display), Public Sans (body) — needs internet at render time |
| Tests | Custom PHP assertion suites under `tests/` (no PHPUnit dependency) |

## Key libraries / components (all in-repo, no Composer)

- `classes/` — domain classes (User, Post, Auth, Authorization, Cache, ...).
- `config/Database.php` — PDO connection, MySQL first, SQLite fallback.
- `lib/TinyRedisClient.php` (see `classes/Cache.php`) — minimal RESP client; the
  app does NOT require the phpredis extension.
- `router.php` — built-in-server router; blocks internal files the way `.htaccess`
  does on Apache. Always pass it: `php -S localhost:8000 router.php`.

## Environment

- XAMPP on Windows is the demo target. MySQL defaults: host `localhost`, user
  `root`, empty password, database `campusconnect` (see `config/Database.php`).
- Redis env overrides: `CC_REDIS_HOST`, `CC_REDIS_PORT`.
- Test DB isolation: tests run against a throwaway SQLite file
  (`CAMPUSCONNECT_TEST_DB`); they never touch the demo database.
- Demo seed: `db/schema.sql` then `db/seed.sql` (7 demo users, all password
  `StrongPass123!`). The `migrate_*.sql` files are ONLY for upgrading pre-existing
  older databases — skip them on a fresh install.
