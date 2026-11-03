# WBS-to-Implementation Mapping (Sprints 1–2)

The official WBS uses **Tweet** terminology; the code uses **Post**. The
mapping is one-to-one and deliberate (approved 2 Nov 2026): renaming the code
would break tests and cost a sprint's worth of churn for no behaviour change.
Read "Tweet" in the WBS as "Post" in the code everywhere below.

## Naming map

| WBS term | Code term | Location |
|---|---|---|
| Tweet | Post (280-char chirp) | `classes/Post.php` |
| `tweets` table | `posts` table | `db/schema.sql` |
| `post_tweet.php` | `post_action.php` (+ `PostController`) | `post_action.php`, `classes/PostController.php` |
| Tweet lifecycle | create → feed → detail → edit → soft-delete | `post_action.php`, `index.php`, `post.php` |
| Tweet feed / timeline | Timeline feed, 10 posts per page | `Post::getFeed()`, `PostController::getTimelineFeed()` |

## Sprint 2 coverage (WBS 2.0 → code)

| WBS item | Implemented as | Status |
|---|---|---|
| 2.2.1 posts schema | `posts` table, FK `user_id` → `users(id)` ON DELETE CASCADE | Done, `50dd377` |
| 2.2.2 feed indexes | `idx_posts_user_id`, `idx_posts_created_at` | Done, `50dd377` |
| 2.3.1 compose box + counter | `index.php` compose form, `maxlength=280`, live `n / 280` counter | Done, `4137b73` |
| 2.3.2 Post class + validation | `Post::validateContent()` (1–280 chars, `mb_strlen`) | Done, `50dd377` |
| 2.3.3 create | `Post::create()` via `PostController::handleCreate()` | Done, `50dd377` |
| 2.3.4 post detail view | `post.php` (author, times, edit/delete for owner/admin) | Done, `4137b73` |
| 2.3.5 edit | `Post::update()` (owner-only, re-validates) | Done, `50dd377` + UI `4137b73` |
| 2.3.6 soft-delete | `Post::delete()` sets `deleted_at`; all reads filter it out | Done, `4137b73` |
| 2.4.1 feed query | `Post::getFeed()` (author join, newest-first) | Done, `50dd377` |
| 2.4.2 feed UI | Chirp cards in `index.php` | Done, `4137b73` |
| 2.4.3 pagination | `getTimelineFeed($page, $limit)`, `has_more`, 10/page | Done |
| 2.5.1 validation tests | `tests/PostTest.php` (boundary, ownership, soft-delete, feed) | Done, extended `4137b73` |
| 2.5.2 XSS QA | `htmlspecialchars()` on all rendered user content; script-tag post renders as text | Verified in 2 Nov browser flow |
| 2.6.1 review notes | `docs/sprint-1-2-review-retro.md` | Done, this docs increment |
| 2.6.2 SRS increment | `docs/sprint-2-srs-increment.md` | Done, this docs increment |
| 2.6.3 diagrams | `docs/uml-class-diagram-erd.md` (class diagram + ERD) | Done, this docs increment |
| 2.6.4/2.6.5 retro notes | `docs/sprint-1-2-review-retro.md` | Done, this docs increment |

## Sprint 1 reference (WBS 1.0 → code, for the diagrams)

| WBS item | Implemented as |
|---|---|
| 1.3.1/1.3.2 users schema | `users` table, unique email |
| 1.3.3 Database class | `config/Database.php` (PDO Singleton, MySQL + SQLite fallback) |
| 1.4.2 registration | `User::register()` with `password_hash()` |
| 1.4.3 login | `User::verifyPassword()` + `Auth::login()` |
| 1.4.4/1.4.5 session safeguards, logout | `Auth` (session fixation/hijack safeguards), `logout.php` |
| 1.4.1 live signup validation | JS password checklist + email validation in `signup.php` (Sprint 2 UI pass) |
| 1.6.1/1.6.2 auth tests | `tests/UserTest.php` |

## Carried forward (approved, not Sprint 2 scope)

- DB-backed sessions (`sessions` table) — Sprint 1 used native PHP sessions;
  a real DB session system is approved work for a later sprint.
- 3-column layout shell + full design system — Sprint 3, combined with the
  visual redesign pass (see retro notes).
- SRS Sections 1–2 (project-level introduction/scope) — still open; this
  increment covers the Sprint 2 section only.
