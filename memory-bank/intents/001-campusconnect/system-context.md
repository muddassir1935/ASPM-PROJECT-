# System Context — Intent 001: CampusConnect

## Actors

| Actor | Role | Can do |
|---|---|---|
| Visitor (logged out) | — | Browse public feed/explore, register, login |
| Student | `student` | Post, reply, like, follow, bookmark, report, search |
| Society Admin | `society_admin` | Everything a student can + post announcements |
| System Admin | `system_admin` | Everything + user/role management, password resets, moderation queue, delete any post/reply |

## External systems

| System | Relationship |
|---|---|
| MySQL / MariaDB (XAMPP) | Primary database. Falls back to SQLite with a visible banner if unreachable. |
| Redis (optional) | Cache backend via `TinyRedisClient` or phpredis; in-memory fallback if absent. |
| Google Fonts | Inter, Bricolage Grotesque, Public Sans — needs internet at render time. |
| Apache / XAMPP | Demo web server on Windows. |
| GitHub | Source of truth for the repo (`muddassir1935/ASPM-PROJECT-`). |

## Boundaries

- No external auth (no OAuth, no SSO) — local accounts only.
- No email sending — hence no emailed password reset or verification.
- No file uploads beyond profile covers/avatars (cover picker uses 6 preset styles).
- Single-database, single-server deployment. No multi-tenancy.
- The app is a course project, not a production service: the "production"
  environment is the instructor's 1-hour review (live demo on XAMPP).

## Constraints

- PHP 8.1+ (no framework, no Composer, no build step) — the code must run on a
  plain XAMPP install.
- Demo data must be pristine and small (7 users / 8 posts / 6 replies /
  11 likes) — tests never touch it (throwaway SQLite test DB).
- All student work is real and attributable: no invented meeting logs or
  contribution figures; contribution register is git history
  (`docs/CONTRIBUTION.md`).
