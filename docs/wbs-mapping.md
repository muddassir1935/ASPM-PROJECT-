# WBS-to-Implementation Mapping (Sprints 1–3)

The official WBS uses **Tweet** terminology; the code uses **Post**. The
mapping is one-to-one and deliberate (approved 2 Nov 2026): renaming the code
would break tests and cost a sprint's worth of churn for no behaviour change.
Read "Tweet" in the WBS as "Post" in the code everywhere below.

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

- DB-backed sessions (`sessions` table) — **done in Sprint 3** (PR #4,
  `6cbe7d4`): `DbSessionHandler` + `Auth::registerDbSessionHandler()`.
  Removed from the carry-forward list.
- 3-column layout shell + full design system — **done in Sprint 3**
  (PR #5 layout shell, PR #6 redesign; `5572167` + `9bbf23e`).
  Removed from the carry-forward list.
- SRS Sections 1–2 (project-level introduction/scope) — still open; this
  increment covers the Sprint 2 section only.

## Sprint 3 coverage (WBS 3.0 → code)

| WBS item | Implemented as | Status |
|---|---|---|
| 3.2 follows schema | `follows` table, composite PK, self-follow CHECK | Done, `6cbe7d4` |
| 3.2 Follow class | `Follow::follow/unfollow/toggle/isFollowing/counts/lists/getSuggestions()` | Done, `6cbe7d4` |
| 3.2 personalized feed | `Post::getPersonalizedFeed()` + `countPersonalized()`, `PostController::getPersonalizedTimelineFeed()` | Done, `6cbe7d4` |
| 3.2 DB-backed sessions | `sessions` table + `DbSessionHandler`, registered in `Auth` | Done, `6cbe7d4` |
| 3.3 profiles | `profile.php`, `User::getById/updateProfile()`, follower modals, `users.bio` | Done, `0c09dab` |
| 3.3 cover styles | `users.cover_style` (6-style whitelist), Edit Profile picker | Done, `9bbf23e` |
| 3.3 search / explore | `User::searchUsers()`, `Post::searchPosts()`, `explore.php` | Done, `6cbe7d4` + `0c09dab` |
| 3.3 3-column layout + design system | Flat divider feed, hairline rails, cobalt accent, Inter, Interlocked CC logo | Done, `0c09dab` + `5572167` |
| 3.4 replies | `replies` table, `Reply` model (280-char, soft-delete), `post.php` thread | Done, `6cbe7d4` + `0c09dab` |
| 3.4 bookmarks | `bookmarks` table, `Bookmark` model, `bookmarks.php` | Done, `6cbe7d4` + `0c09dab` |
| 3.5 social tests | `tests/SocialTest.php` (49 tests) + `tests/SessionTest.php` (9 tests) | Done, `6cbe7d4` |
| 3.6 docs increment | `docs/sprint-3-srs-increment.md`, retro + UML + mapping updates | Done, this docs increment |

## Sprint 4 coverage (WBS 4.0 → code)

| WBS item | Implemented as | Status |
|---|---|---|
| 4.1 likes schema | `likes` table, composite PK `(user_id, post_id)`, indexes | Done (`db/schema.sql`, `db/migrate_sprint4_mysql.sql`) |
| 4.1 Like class | `Like::add/remove/toggle/isLiked/countByPost/countByUser/getLikedPosts/getUsersWhoLiked` | Done (`classes/Like.php`) |
| 4.1 like actions & UI | `like_action.php` AJAX handler, heart SVG, live counter, profile Likes tab | Done (`like_action.php`, `includes/social_widgets.php`, `profile.php`) |
| 4.2 hashtags schema | `hashtags`, `post_hashtags` tables, unique tags | Done (`db/schema.sql`, `db/migrate_sprint4_mysql.sql`) |
| 4.2 Hashtag class | `Hashtag::extractTags/syncPostTags/renderHashtagLinks/getTrending/getPostsByTag` | Done (`classes/Hashtag.php`) |
| 4.2 hashtag UI & explore | Hashtag links in posts, `explore.php?tag=...` filtering, Trending Topics widget | Done (`includes/social_widgets.php`, `explore.php`, `index.php`) |
| 4.3 Redis & memory caching | `Cache` Singleton, Redis support with graceful zero-downtime memory fallback | Done (`classes/Cache.php`) |
| 4.3.1 TinyRedisClient | Pure-PHP RESP socket client (PING/GET/SET/SETEX/DEL/INCR/SCAN/KEYS), used when the phpredis extension is absent | Done — added in the 9 Nov verification pass (`classes/TinyRedisClient.php`); verified against a real Redis 7.0.15 server |
| 4.3 timeline caching | Personalized + global feeds cached, 300s TTL; like counters cached (cache-aside, INCR on like, invalidate on unlike) | Done — TTL corrected 60s → 300s and global feed + counters added in the verification pass |
| 4.3 cache invalidation | Automatic `feed_` prefix invalidation on post create/update/delete, like toggle, follow/unfollow, reply create/delete, bookmark add/remove | Done (`classes/Post.php`, `like_action.php`, `classes/Follow.php`, `classes/Reply.php`, `classes/Bookmark.php`) |
| 4.4 benchmark + dashboard | `benchmark.php` CLI report; web view renders an SVG bar-chart dashboard | Done (basic single-chart dashboard, verification pass) |
| 4.4.4 N+1 audit | Measured statement counts per endpoint | Done — `docs/n-plus-1-audit-sprint4.md` (no PHP-level N+1 found) |
| 4.4 sprint 4 tests | `tests/Sprint4Test.php` (comprehensive unit tests for Likes, Hashtags, and Cache) | Done (48 tests after the verification pass) |
| 4.5 docs increment | `docs/sprint-4-srs-increment.md` | Done |

## Carried forward to Sprint 5 (13–15 Nov)

- In-app Notifications (`notifications` table, header bell badge) — Sprint 5.
- Full security audit & CSRF hardening — Sprint 5.
- Realistic university seed data & demo fixtures — Sprint 5.
- Final deployment guide & project retro — Sprint 5.
