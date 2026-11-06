# Sprint 3 — SRS Increment (Social, Profiles, Discovery, Sessions, Design System)

> Status: Sprint 3 is functionally complete in `main` (PR #4 core, PR #5 UI,
> PR #6 design, all merged 5 Nov 2026; `main` at `7fe4dda`). This increment
> documents what was actually built, mapped to the official WBS. It does not
> introduce new requirements.
> Naming: the WBS says **Tweet**; the code says **Post**. They are the same
> thing — see `wbs-mapping.md`.

## 1. Purpose and scope

Sprint 3 (WBS 3.0) turns CampusConnect from a single-user timeline into a
social product: users follow each other and see a personalized feed, reply to
posts in threads, bookmark posts, discover people and posts through search and
explore, and manage a profile with a bio and a cover style. It also closes the
two Sprint 1–2 carry-forwards that Sprint 3 owned: DB-backed sessions (the
approved Problem 2 decision) and the 3-column layout with a full design
system, including the visual redesign pass the user required instead of the
generic AI-looking Sprint 2 UI.

Out of scope for Sprint 3 (carried forward): likes, hashtags, Redis caching
(Sprint 4); notifications, the full security audit, seed data and the
deployment guide (Sprint 5); photo upload for covers or avatars (deferred —
cover *styles* ship instead, see FR-3.6).

## 2. Functional requirements

### FR-3.1 Follow / unfollow (WBS 3.2, 3.3)

- `Follow::follow()` / `unfollow()` / `toggle()` in `classes/Follow.php`;
  `follow_action.php` handles the request (AJAX toggle from the feed and
  profile pages).
- A follow is one row in `follows` (`follower_id`, `followed_id`, composite
  primary key); self-follow is rejected by the `chk_follows_no_self` CHECK
  constraint as well as in code.
- `countFollowers()` / `countFollowing()` feed the profile counters;
  `getFollowers()` / `getFollowing()` (limit 50) feed the profile modals.
- `getSuggestions()` ("who to follow", limit 5) ranks users the viewer does
  not already follow by follower count; it is rendered by
  `includes/social_widgets.php` in the right rail.

### FR-3.2 Personalized feed (WBS 3.2)

- `Post::getPersonalizedFeed($userId)` returns the viewer's own posts plus
  posts from users they follow, newest-first (`created_at DESC, id DESC`),
  excluding soft-deleted posts; `countPersonalized()` gives the matching
  total. `PostController::getPersonalizedTimelineFeed()` adds pagination
  metadata, same shape as the Sprint 2 timeline feed.
- The old `Post::getFeed()` (all posts, newest-first) is kept and still
  powers the explore page; the home feed (`index.php`) now uses the
  personalized feed. Every feed row carries `reply_count` (non-deleted
  replies only).
- Verified live 5 Nov 2026: with the demo data, the feed showed only the
  viewer's and followed users' posts (3 posts); a post from an unfollowed
  user appeared on explore but not in the feed.

### FR-3.3 Threaded replies (WBS 3.4.1)

- `Reply` (`classes/Reply.php`) mirrors the `Post` model: `MAX_LENGTH = 280`,
  `MIN_LENGTH = 1`, `validateContent()` (multibyte-safe), `create()`,
  `getByPostId()` (limit 100), `countByPost()`, and soft-delete via
  `delete()` (sets `replies.deleted_at`; author or admin only, same rule as
  post delete).
- `post.php?id=<id>` shows the post with its reply thread underneath and a
  reply box with the same 280-character counter as compose;
  `reply_action.php` handles create/delete with Post/Redirect/Get.
- Replies are soft-deleted, never hard-deleted, so counts and threads stay
  consistent with the Sprint 2 post rule.

### FR-3.4 Bookmarks (WBS 3.4)

- `Bookmark` (`classes/Bookmark.php`): `add()` / `remove()` / `toggle()` /
  `isBookmarked()` / `getBookmarkedPosts()` / `countByUser()`. One row per
  (`user_id`, `post_id`) in `bookmarks` (composite primary key).
- `bookmarks.php` lists the user's saved posts; `bookmark_action.php`
  handles the toggle (AJAX from feed, explore and post detail).
- Known minor gap (recorded 5 Nov 2026, not a defect for this sprint): the
  saved state is shown by the word "Saved" only, not by a filled icon, and
  the compose box is a fixed 58px height with no auto-grow on long posts.
  Both are small JS follow-ups, not Sprint 3 scope.

### FR-3.5 Profiles, search and explore (WBS 3.3, 3.4)

- `profile.php` shows a user's bio, follower/following counts (with list
  modals), their posts, and an Edit Profile control for the owner.
  `User::updateProfile()` writes name, bio and cover style
  (`profile_action.php`); `User::getById()` returns the profile row with
  follower count attached.
- `User::searchUsers($query, $excludeUserId, $limit = 20)` searches by name
  (LIKE with escaped wildcards) and returns follower counts, so the viewer
  can follow from the results.
- `explore.php` combines search (people via `searchUsers`, posts via
  `Post::searchPosts()` — LIKE with escaped wildcards) with a latest-posts
  list; it is the only feed surface that shows posts from users the viewer
  does not follow.
- `users.bio` is a nullable TEXT column (`db/schema.sql`; added by
  `db/migrate_sprint3_mysql.sql` on existing MySQL installs only if missing).

### FR-3.6 Cover styles (WBS 3.3 — user-chosen addition, 5 Nov 2026)

- The profile cover is a designed band, not a photo: `users.cover_style`
  (`VARCHAR(20)`, default `'signature'`) holds one of six whitelisted
  styles — `signature`, `cobalt`, `teal`, `sunset`, `violet`, `graphite`
  (`User::COVER_STYLES`, `User::DEFAULT_COVER_STYLE = 'signature'`).
- `User::normalizeCoverStyle()` rejects any other value server-side and
  falls back to the default, so a crafted request cannot inject an
  arbitrary class or value. The Edit Profile modal offers the six styles as
  preview swatches; the chosen style renders on that user's profile for
  every viewer, and persists across reloads (verified end-to-end 5 Nov 2026).
- Photo upload was explicitly deferred by the user in favour of styles:
  no upload handling, file validation or storage was built in this sprint,
  so there is no new upload attack surface. Proper photo upload belongs to
  a later sprint if the user asks for it.

### FR-3.7 DB-backed sessions (WBS 3.2 — approved Problem 2 decision)

- `DbSessionHandler` (`classes/DbSessionHandler.php`) implements PHP's
  `SessionHandlerInterface` against the `sessions` table (`id VARCHAR(128)`
  PK, `user_id` NULL, `data` TEXT, `last_activity` INT): `open` / `close` /
  `read` / `write` / `destroy` / `gc` (expiry sweep by `last_activity`) plus
  `setUserId()` to attach the logged-in user to the session row.
- `Auth::registerDbSessionHandler()` registers it via
  `session_set_save_handler()` when a PDO connection is available, and
  `Auth::login()` calls `setUserId()`; if the handler cannot be registered
  (no PDO), native PHP sessions remain the fallback, so login never breaks.
- This closes the Sprint 1 gap ("no separate `sessions` table") recorded in
  `sprint-1-2-review-retro.md`. Session data now lives in the database
  instead of files; the session lifecycle was confirmed by CLI on 5 Nov 2026.

### FR-3.8 Design system and 3-column layout (WBS 3.3, Problem 7)

- The layout is the approved 3-column shell: left navigation rail, centre
  feed, right rail (search + who-to-follow), collapsing to a single column
  with an icon rail on mobile (verified 5 Nov 2026).
- The visual system is the product-grade redesign that replaced the
  Sprint 2 look, in the `feature/sprint-3-design` stack (squashed to
  `5572167` + `9bbf23e` in `main`): flat white surface, posts separated by
  hairline dividers instead of floating cards, one cobalt accent
  (`#2B3FE0`) used only where action is needed, Inter typography, and
  borderless widgets. No welcome banner, no per-post cards or shadows.
- Brand character is spent in exactly two places: the auth brand panel and
  the profile cover band, using one ink → indigo → cobalt signature
  duotone. The logo is the user-chosen **Interlocked CC** mark (two
  interlocked "C" arcs, Campus + Connect), applied in the topbar, the auth
  panel and as a ghost watermark; it replaced the earlier arc+node mark
  after the user picked Concept 1 from a three-concept comparison sheet
  (the assistant's recommendation had been Concept 2, Nodes C — the user's
  choice is final and is what shipped).
- Admin posts carry only a small muted "Announcement" label; there is no
  large coloured admin treatment.
- The design CSS lives in `includes/header.php` (the app's shared shell);
  avatars are initial-based circles in lively, saturated tones.
- Functionality was not changed by the redesign: form fields, AJAX
  toggles, counters and all routes are the same, and the full suite stayed
  green (see §5).

## 3. Data model (WBS 3.2, 3.3, 3.4)

`db/schema.sql` adds four tables; `db/migrate_sprint3_mysql.sql` applies the
same changes to an existing MySQL install (bio column only if missing), and
`db/migrate_cover_style_mysql.sql` adds `users.cover_style`. The SQLite
fallback in `config/Database.php` auto-migrates the same columns.

| Table | Columns | Notes |
|---|---|---|
| `follows` | `follower_id`, `followed_id`, `created_at` | Composite PK (`follower_id`, `followed_id`); both FK → `users(id)` ON DELETE CASCADE; `CHECK (follower_id <> followed_id)`; index on `followed_id` |
| `replies` | `id`, `post_id`, `user_id`, `content`, `created_at`, `updated_at`, `deleted_at` | FKs → `posts(id)` and `users(id)` ON DELETE CASCADE; soft-delete via `deleted_at`; indexes on `post_id`, `created_at` |
| `bookmarks` | `user_id`, `post_id`, `created_at` | Composite PK (`user_id`, `post_id`); FKs → `users(id)` and `posts(id)` ON DELETE CASCADE; index on `post_id` |
| `sessions` | `id VARCHAR(128)`, `user_id` NULL, `data` TEXT, `last_activity` INT | PK `id`; FK `user_id` → `users(id)` ON DELETE CASCADE; indexes on `user_id`, `last_activity` (gc sweep) |
| `users` (changed) | + `bio TEXT NULL`, + `cover_style VARCHAR(20) NOT NULL DEFAULT 'signature'` | `cover_style` whitelist enforced in `User`, not by the column |

## 4. Non-functional requirements

- **Security.** All new queries use PDO prepared statements with bound
  parameters (`Follow`, `Reply`, `Bookmark`, `User::searchUsers`,
  `Post::getPersonalizedFeed` / `searchPosts`); LIKE wildcards in search
  input are escaped so they match literally. All user-controlled output in
  the new pages is escaped with `htmlspecialchars()`.
- **Authorization.** Write paths check login state in the action scripts
  and ownership/admin role inside the models (`Reply::delete`,
  `Post::delete`): follow/bookmark/reply actions require a logged-in user,
  and hidden buttons are not the security boundary.
- **Testability.** The new models take PDO by injection, like `Post` and
  `User`, so the suites run without a web server (SQLite fallback).
- **Backwards compatibility.** The Sprint 2 `getFeed()` / timeline contract
  is unchanged; the home feed only switches its source to
  `getPersonalizedFeed()`. Existing MySQL installs migrate with the two SQL
  files; the user must run `db/migrate_sprint3_mysql.sql` then
  `db/migrate_cover_style_mysql.sql` in phpMyAdmin on the XAMPP demo
  machine before demoing Sprint 3 (still pending at the time of writing).

## 5. Verification (evidence, not claims)

- `tests/SocialTest.php` — follows (follow/unfollow/toggle, self-follow
  rejected, counts, suggestions), replies (validation, threading, owner/
  admin delete, soft-delete invisibility), bookmarks (add/remove/toggle,
  list), personalized feed (only own + followed posts), search.
  Run: `php tests/SocialTest.php`.
- `tests/SessionTest.php` — DB session handler lifecycle (open/read/write/
  destroy/gc, user attachment) plus the `Auth` registration path.
- `tests/UserTest.php` — Sprint 1 auth regression plus the new cover-style
  test (style saves; invalid value falls back to `'signature'`).
- `tests/PostTest.php` — Sprint 2 regression (unchanged behaviour).
- Browser flow verified 5 Nov 2026: login, personalized feed (3 posts,
  followed users only), profile (3 followers / 2 following), explore
  (unfollowed user's post visible, bookmark "Saved" state), post detail
  (2 threaded replies + reply box), cover-style picker (Teal selected,
  saved, persisted after reload), and all pages login → post detail plus
  mobile collapse, in the `previews/sprint3-design-v2/` screenshots.
- Bug found and fixed during verification: `includes/social_widgets.php`
  used the wrong path for `Follow.php` (`includes/` instead of `classes/`),
  which truncated index, explore, profile and bookmarks at the sidebar —
  no who-to-follow widget, no footer JS (AJAX toggles dead), no profile
  modals. Fixed in the design stack (`f44e1be` on the branch; in `main`
  via the PR #6 squash) and live-render verified afterwards.

## 6. Latest local test run

- Date: 5 Nov 2026, on the Sprint 3 branches (reported in chat the same
  day; tests were **105 / 105** — UserTest 18/18, PostTest 29/29,
  SocialTest 49/49, SessionTest 9/9 — on a fresh database).
- This docs run did **not** re-run the suites: PHP is not installed in
  this research workspace right now (`php: command not found`), the same
  fragility noted in the Sprint 1–2 retro. The counts above are the
  5 Nov verification, not a fresh run; re-run `php tests/*.php` (or the
  XAMPP fallback) before citing them in a demo.
- Known test caveat (pre-existing, unrelated to Sprint 3 UI work):
  `SocialTest` can show 48/49 on a test-crowded database because
  suggestions rank by follower count and old test users leak in; fresh
  database gives 49/49. Flagged for the Sprint 5 test-database cleanup.
- `php -l` was clean on the changed files at verification time.
