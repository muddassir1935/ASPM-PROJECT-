# System Architecture — CampusConnect

## Request flow

Browser → Apache/XAMPP (or `php -S localhost:8000 router.php`) → page script
at repo root → domain classes in `classes/` → `config/Database.php` (PDO) →
MySQL (or SQLite fallback). AJAX calls hit `*_action.php` endpoints and return
JSON. `.htaccess` / `router.php` block direct web access to `db/`, `tests/`,
`config/`, `classes/`, `docs/`, `benchmark.php` internals.

## Domain model (12 tables)

`users`, `posts`, `follows`, `replies`, `bookmarks`, `sessions`, `likes`,
`hashtags`, `post_hashtags`, `notifications`, `login_attempts`, `reports`.

- Posts are 280-character "chirps" (code calls them Post; docs call them Tweet —
  see `docs/wbs-mapping.md` for the naming map).
- `posts.is_announcement` marks society announcements.
- `sessions` rows are server-side sessions (DB-backed); a role change or
  deactivation deletes the user's rows, revoking their sessions.
- `reports` feeds the admin moderation queue (post reporting, Sprint 5).

Key classes: `User`, `Post`, `Auth`, `Authorization`, `Cache`, `Notification`,
`Report`, `Hashtag`, `Follow`, `Like`, `Reply`, `Bookmark`. Full class diagram +
ERD: `docs/uml-class-diagram-erd.md`.

## RBAC

Three roles: `student`, `society_admin`, `system_admin`. Legacy values map
(`admin` → `system_admin`, `society` → `society_admin`). `Authorization` holds
the permission map (14 permissions, e.g. `post.create`, `announcement.create`,
`report.review`, `role.assign`) and fails closed on unknown permissions. `Auth`
refreshes the role from the DB on every request. Society admins are NOT scoped
to one society (known limitation, documented in the security audit).

## Caching

`Cache` tries, in order: phpredis (if the extension is present) →
`TinyRedisClient` (pure-PHP RESP, no extension needed) → in-memory fallback.
`benchmark.php` shows which backend is active. Redis is optional; the app works
fully without it.

## MySQL → SQLite fallback

If MySQL is unreachable, `Database` falls back to a local SQLite database and
the UI shows a visible banner. The fallback is deliberately LOUD — a silent
fallback once served a different, empty database without anyone noticing.

## Frontend

Server-rendered pages, X-style 3-column shell (nav / feed / sidebar), vanilla JS
for likes/bookmarks/follows (AJAX + CSRF). Cover picker with 6 styles. No SPA,
no build step. See `ux-guide.md`.
