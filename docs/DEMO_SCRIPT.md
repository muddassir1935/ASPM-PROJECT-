# CampusConnect — Live Demo Script (Sprint 5 Review)

> For the final review (Sun 15 Nov 2026). ~12 minutes end-to-end.
> Every step below was walked against the real app on the demo seed data
> (`db/seed.sql`: 7 users / 8 posts). Nothing here needs a second machine —
> one laptop, XAMPP + the repo folder, done.

## 0. Before the review (5 minutes)

1. Start XAMPP **Apache** + **MySQL** (XAMPP Control Panel).
2. Optional but recommended — start Redis so the benchmark shows the real
   Redis tier: in the folder with the binaries, run `redis-server.exe`
   (foreground window, keep it open). Without Redis the app still works —
   it falls back to the in-memory cache — but the benchmark page will say
   `MemoryFallback` instead of `Redis`.
3. Open **http://localhost/campusconnect** and confirm the landing page loads
   with no amber banner. (An amber "running on the SQLite fallback" banner
   means MySQL is down — start it and reload; do not demo on the fallback
   database, it holds different data.)
4. Have the three demo logins ready (all passwords `StrongPass123!`):
   - `student@nu.edu.pk` — Ali Ahmed (student)
   - `society@nu.edu.pk` — ACM NUCES Chapter (society admin)
   - `admin@nu.edu.pk` — Campus Administrator (system admin)
5. Optional: open a terminal at the repo folder for step 8
   (`php tests/run_all.php`).

## 1. Landing + signup/login (1 min)

- Show the public landing page: hero, feature cards, **Create Your Account**.
- Log in as `student@nu.edu.pk`. Point out the 3-column shell: left nav,
  feed, right rail (Trending Topics + Who to follow).

## 2. Post, hashtag, like, reply (3 min)

- Compose a post from the feed box, e.g.
  `Midterm week survival guide — library opens at 8am! #FASTNUCES #exams`
  (280-char counter visible; Post button enables only with text).
- The post appears at the top of the feed with a relative timestamp
  ("just now"). Click it to open the **post detail page** (same 3-column
  shell, replies underneath).
- Click the `#FASTNUCES` hashtag in the post — the Explore page filters to
  that tag.
- Back on the feed: **like** a post (heart fills + pops, counter updates),
  **reply** to it, **bookmark** it (Save → Saved; it appears on the
  Bookmarks page in the left nav).

## 3. Explore + people (2 min)

- Open **Explore**: search for `Zainab` (people results with Follow
  buttons), then search `library` (post results). Note the Previous/Next
  pager under the post list.
- **Follow** Zainab from the search results (button flips to Following;
  hovering it shows Unfollow). Open her profile: cover band, bio, stats,
  Posts/Likes tabs, Followers/Following modals.
- On your own profile, open the **cover picker** (6 styles) and switch the
  cover — it persists on reload. The same Edit Profile modal holds
  **Change password** (current password required; other sessions signed out).

## 4. Notifications (1 min)

- Click the **bell** in the top bar: the unread badge shows the count;
  the notifications center lists follow/like/reply events with links back
  to the post or profile. Mark one read (or Mark all read).

## 5. Society announcement (1 min)

- Log out, log in as `society@nu.edu.pk` (ACM NUCES Chapter).
- Compose a post with **Post as announcement** checked. It renders with the
  Announcement badge + megaphone in the feed. (Students never see that
  checkbox — it is permission-gated.)

## 6. Admin panel + moderation (2 min)

- Log out, log in as `admin@nu.edu.pk`.
- Open **Admin** in the left nav: searchable user list with a role dropdown
  per user. Change a student's role (e.g. Bilal → Society Admin) — note the
  confirmation says their sessions are signed out; change it back after.
- Moderation: delete any post from its card or detail page (admin Delete).
  Soft-deleted posts disappear from feeds but stay in the database.
- Password reset: click **Reset password** on a user row — a 16-character
  temporary password is shown once (share it with the user directly; the
  app sends no email). Their old password stops working immediately.

## 7. Benchmark (1 min)

- Open **http://localhost/campusconnect/benchmark.php** (admin-only page —
  logged-out visitors are sent to login, non-admins get 403).
- Read the table: direct-DB average vs cached-read average, and the speedup
  callout. The header line names the cache driver/backend, so the reviewer
  can see whether Redis or the memory fallback served it.

## 8. Tests (1 min, optional)

- In the terminal: `php tests/run_all.php` → **286/286 green**
  (UserTest 24, PostTest 29, SocialTest 52, NotificationTest 28,
  SessionTest 9, Sprint4Test 52, RbacTest 92).

## 9. Close

- Recap the arc in one line per sprint: S1 auth → S2 posts/feed →
  S3 social/profiles → S4 likes/hashtags/Redis → S5 notifications/RBAC/
  hardening/polish. Offer the report (`AIR_Report.pdf`) and deck
  (`AIR_Presentation.pptx`) for the numbers.

## If something goes wrong

| Symptom | Fix |
|---|---|
| Amber fallback banner on any page | MySQL is down — start it in XAMPP, reload. Do not continue on SQLite (different data). |
| `localhost` refuses to connect | Apache is down — start it in XAMPP. If port 80 is busy (Skype/VMware), stop that app or move Apache to 8080 (`http://localhost:8080/campusconnect`). |
| Benchmark says `MemoryFallback` | Redis is not running — start `redis-server.exe`, reload the page. The app itself is unaffected. |
| Login says "invalid email or password" for a seed account | Wrong database (see banner) or the seed was never imported — re-import `db/schema.sql` + `db/seed.sql` in phpMyAdmin (see `docs/DEPLOYMENT.md`). |
| "Too many failed login attempts" | The rate-limit locked that email for 15 min after 5 failures. Wait, or use another seed account. |
| A page shows a raw error | Do not refresh blindly — note the URL and step, and continue the demo from the feed. |
