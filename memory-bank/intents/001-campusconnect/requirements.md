# Requirements — Intent 001: CampusConnect

Status: **Delivered** (submitted 16 Nov 2026 from build `424390e`).
Source of truth for detail: `docs/sprint-*-srs-increment.md` (per-sprint
requirements, as built) and `docs/wbs-mapping.md`.

## What it is

A centralized university microblogging & announcement hub for FAST-NUCES
students and societies: short posts ("chirps", 280 characters), follows,
likes, replies, bookmarks, hashtags, search, notifications, and
role-gated announcements — with an admin panel and a moderation queue.

## Functional requirements (all delivered)

1. **Auth** — Register (campus email guard), login, logout, sessions;
   change-own-password; admin password reset. 30-minute idle timeout.
2. **Posts** — Create 280-char chirps, edit own, delete own (modal confirm),
   view post detail with replies. Double-submit must not duplicate a post.
3. **Social** — Follow/unfollow, like/unlike, reply, bookmark, repost count
   display; profile pages with Posts / Replies / Likes tabs.
4. **Discovery** — Explore feed, hashtag pages + trending, name-only user
   search (emails are never public).
5. **Notifications** — Like / follow / reply / mention notifications with
   unread state.
6. **RBAC** — Roles `student`, `society_admin`, `system_admin`; 14 permissions
   enforced centrally (`Authorization`), admin panel for user/role management.
7. **Announcements** — Society admins post announcements (`is_announcement`);
   students see them in the feed.
8. **Reporting & moderation** — Report a post; admin queue to dismiss or
   action (soft-delete the violating post, co-reports actioned together).
9. **Responsive UI** — X-style 3-column shell; tablet 2-column; mobile single
   column with bottom nav; no horizontal scroll at 390px.

## Non-functional requirements

- **Security**: PDO prepared statements; `password_hash`; CSRF on all
  state-changing actions; login rate limit (5/15 min); security headers;
  `.htaccess`/`router.php` protect internal directories. No stored/reflected
  XSS, no SQLi, no IDOR (verified by the 12 Nov security audit: 0 Critical).
- **Tests**: full suite green — 412 tests (398 SQLite + 14 MySQL).
- **Demo**: runs on XAMPP (Windows) from `db/schema.sql` + `db/seed.sql`
  in under 5 minutes; Redis optional.
- **Performance**: no N+1 on the feed/profile hot paths (the profile-modal
  N+1 found in the audit was fixed); cache layer present (Redis or memory).

## Known limitations (documented, not hidden)

- No emailed forgot-password link (no mail infrastructure).
- Society admins are not scoped to one society.
- No email verification on signup.
- UI polish items cut for time in the final pass: aria-labels (M-9), Explore
  pagination (M-12), touch targets (M-13), auth watermark (M-14), empty-state
  alignment (M-16), who-to-follow truncation (M-17).
