# Deployment Config — CampusConnect (Operations)

## Target environment

XAMPP on Windows (the instructor's review machine is the user's laptop).
No cloud deployment exists and none is planned.

## Fresh install (5 minutes)

1. Clone the repo into `C:\xampp\htdocs\campusconnect`.
2. Start Apache + MySQL from the XAMPP Control Panel.
3. Import `db/schema.sql` (creates the `campusconnect` DB + 12 tables),
   then `db/seed.sql` (7 demo users, all password `StrongPass123!`).
   Do NOT run the `migrate_*.sql` files on a fresh install — they are only
   for upgrading pre-existing older databases.
4. Open `http://localhost/campusconnect/`.
5. If the SQLite-fallback banner shows, MySQL is not running.

Full guide: `docs/DEPLOYMENT.md`. Demo accounts: `admin@nu.edu.pk`
(system_admin), `society@nu.edu.pk` + `ieee@nu.edu.pk` (society_admin),
`student@nu.edu.pk` (student) — all `StrongPass123!`.

## Redis (optional)

- App works fully without Redis (in-memory fallback). `benchmark.php` shows
  the active backend.
- Old MSOpenTech 3.0 binaries found on the user's machine at
  `C:\Users\Muddassir\OneDrive\Desktop\university\Sem 6\SE\PROJECT\SE PROJECT\redis\`
  — run `redis-server.exe` from that folder (foreground, keep the window
  open), verify with `redis-cli.exe ping` → PONG. Sufficient: the app uses
  basic GET/SET only. Not on PATH, not a service.
- Cleaner alternative: Memurai Developer Edition (free, installs as a
  Windows service on 6379).

## Release process

- Release artifacts: `[GroupCode]_Presentation.pptx`,
  `[GroupCode]_Report.pdf`, `[GroupCode]_WorkingSoftware.zip`.
- The zip is built by `scripts/package_release.py` — run it ONLY for the
  final packaging step, never casually.
- Release tag: needs the user's explicit approval (still pending as of
  2026-11-17).
- Submitted build: `424390e` (16 Nov 2026). Do not rebuild the submission
  from a later commit without the user deciding so.

## Verification after deploy

- `php tests/run_all.php` — full suite green (412 tests).
- Live smoke: login as each demo role, post a chirp, like, follow, check
  the admin panel loads for the system admin only.
- Check the SQLite-fallback banner is ABSENT (MySQL path in use).
