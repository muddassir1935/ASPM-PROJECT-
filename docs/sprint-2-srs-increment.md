# Sprint 2 — SRS Increment (Post Engine & Feed)

> Status: Sprint 2 is functionally complete in `main` (PR #1 backend, PR #2 UI,
> both merged 2 Nov 2026). This increment documents what was actually built,
> mapped to the official WBS. It does not introduce new requirements.
> Naming: the WBS says **Tweet**; the code says **Post**. They are the same
> thing — see `wbs-mapping.md`.

## 1. Purpose and scope

CampusConnect is a university microblogging & announcement hub (PHP OOP +
MySQL/PDO). Sprint 2 (WBS 2.0) adds the post engine: users publish 280-character
posts ("chirps"), see a reverse-chronological timeline feed, open a post detail
page, and edit or soft-delete their own posts.

Out of scope for Sprint 2 (carried forward): follows, profiles, search, replies,
bookmarks (Sprint 3); likes, hashtags, Redis caching (Sprint 4); notifications
and the full security audit (Sprint 5); the 3-column layout and design system
(Sprint 3, with the visual redesign pass).

## 2. Functional requirements

### FR-2.1 Compose a post (WBS 2.3.1, 2.3.2, 2.3.3)

- A logged-in user writes a post in the compose box on the feed page
  (`index.php`) and submits it to `post_action.php`.
- Content is trimmed, then validated by `Post::validateContent()`:
  1–280 characters (multibyte-safe via `mb_strlen`), empty or whitespace-only
  content rejected.
- The compose box enforces the same limit in the browser (`maxlength="280"`)
  with a live `n / 280` character counter and a disabled submit button while
  the box is empty; server-side validation is authoritative.
- `PostController::handleCreate()` rejects unauthenticated requests before
  touching the model. Successful creation redirects (Post/Redirect/Get), so a
  browser refresh never re-submits.

### FR-2.2 Post detail page (WBS 2.3.4)

- `post.php?id=<id>` shows one post with author name, role, email, posted time
  and a relative time label (`Post::formatTimeAgo()`), plus an "edited" marker
  when `updated_at` differs from `created_at`.
- The owner sees an inline edit form and a delete button; admins also see the
  delete button. Soft-deleted or unknown posts show a "not found" state.
- Edits go through `PostController::handleUpdate()` → `Post::update()`, which
  re-validates content and checks ownership before writing.

### FR-2.3 Edit and soft-delete (WBS 2.3.5, 2.3.6)

- Edit: only the original author, only for posts that are not soft-deleted.
- Delete: soft-delete — `Post::delete()` sets `posts.deleted_at` to the current
  timestamp instead of removing the row. Author or admin only.
- Soft-deleted posts are excluded everywhere they would otherwise appear:
  `getById()`, `getFeed()`, `getByUserId()`, `countByUser()` and `countAll()`
  all filter on `deleted_at IS NULL`. After a delete the user is always sent
  back to the feed, since the detail page no longer exists for that post.

### FR-2.4 Timeline feed (WBS 2.4.1, 2.4.2, 2.4.3)

- `Post::getFeed()` returns posts newest-first (`created_at DESC, id DESC`),
  joined to `users` for author name, email and role, excluding soft-deleted rows.
- `PostController::getTimelineFeed($page, $limit)` adds pagination metadata
  (`total`, `page`, `has_more`). The feed page shows 10 posts per page.
- Feed cards show the author initial avatar, name, role, relative time, full
  content, and (for the owner) inline edit and delete controls.

### FR-2.5 Data model (WBS 2.2.1, 2.2.2)

`db/schema.sql` defines `posts`:

| Column | Type | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT PRIMARY KEY | |
| `user_id` | INT NOT NULL | FK → `users(id)` ON DELETE CASCADE |
| `content` | TEXT NOT NULL | 1–280 chars enforced in `Post`, not by the column |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP, auto-updated on UPDATE | |
| `deleted_at` | TIMESTAMP NULL DEFAULT NULL | NULL = visible; set = soft-deleted |

Indexes: `idx_posts_user_id` on `user_id`, `idx_posts_created_at` on
`created_at DESC` — the two access paths the feed and per-user queries use.
`config/Database.php` additionally keeps a zero-config SQLite fallback (used by
the test suites when MySQL/XAMPP is off) with the same tables, indexes, and a
migration that adds `deleted_at` to older SQLite files.

## 3. Non-functional requirements

- **Security (WBS 2.5.2).** All queries use PDO prepared statements with bound
  parameters (`Post`, `PostController`); no user input is interpolated into SQL.
  All user-controlled output in `index.php`, `post.php` and `signup.php` is
  escaped with `htmlspecialchars()`, so post content renders as text, never as
  markup (verified by the XSS check in the 2 Nov browser flow: a script-tag
  test post rendered as safe text).
- **Authorization.** Every write path checks login state (`Auth::isLoggedIn()`
  in `post_action.php`) and ownership/admin role inside the model, not only in
  the UI — hidden buttons are not the security boundary.
- **Testability.** `Post` and `PostController` take their PDO connection /
  current user by injection, so the suites run without a web server.

## 4. Verification (evidence, not claims)

- `tests/PostTest.php` — validation (empty, whitespace, 280-char boundary,
  over-limit), CRUD, ownership (intruder edit/delete denied), admin delete,
  soft-delete invisibility, feed ordering and pagination.
  Run: `php tests/PostTest.php` (see §5 for the latest run).
- `tests/UserTest.php` — Sprint 1 auth regression (unchanged by Sprint 2
  except the `Auth.php` one-line change in commit `50dd377`).
- Browser flow verified 2 Nov 2026: signup with live password checklist,
  login, compose with counter, feed rendering, post detail, edit, soft-delete,
  and the XSS rendering check above.

## 5. Latest local test run

- Date: 3 Nov 2026, on branch `feature/sprint-2-docs` (base `main` at `f496a09`).
- PHP 8.3.6 (cli) with `pdo_sqlite` + `mbstring`, reinstalled in the workspace
  that morning (the 2 Nov install had not survived the VM change); XAMPP
  remains the fallback. `php -l` clean on all tracked PHP files.
- `php tests/UserTest.php`: **17 / 17 passed**.
- `php tests/PostTest.php`: **29 / 29 passed**.
