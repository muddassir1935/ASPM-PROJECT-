# CampusConnect — Code, Feature & Documentation Audit

**Date:** 15 Nov 2026 · **Audited commit:** `f61e5c6` (main) · **Status:** all findings handled — see *Resolution status* below.

**How this was checked:**
- The full test suite was run with `php tests/run_all.php` (286/286 pass).
- The app was run locally on MySQL 9 and PHP 8.5, and findings were tested over HTTP with curl. **Confirmed** means the problem was reproduced. **Code** means it was found by reading the source and confirming the logic.
- Severity: **High** breaks a feature, misleads an evaluator, or is a real security exposure. **Medium** is a real defect with limited reach. **Low** is a quality or consistency problem.

---

## Resolution status (15 Nov 2026)

All fixes were made on top of main `424390e` (after PR #15 — the reporting feature merged during the audit was reviewed with the rest) and committed as six grouped commits; the *Commit* column names the group. The regression tests for every fix are in the `test:` commit. The test suite grew from 322 to **412** tests, including a new real-MySQL suite (`tests/MysqlCompatTest.php`).

| Group | Commit subject |
|---|---|
| security | `fix(security,db): block internal files, strict sessions, UTC times, safer login and cache` |
| core | `fix(core): MySQL search, RBAC enforcement, live feed counts, notification cleanup` |
| ui | `fix(ui): pagination, return-to-origin, accessibility, error feedback, fallback banner` |
| docs | `docs: correct setup, upgrade path, test counts, UML and contribution register` |
| deliverables | `build(deliverables): regenerate report, deck and velocity chart (audit D3)` |

| ID | Outcome | Commit | Note |
|---|---|---|---|
| C1 | Fixed | core | |
| C2 | Fixed | security | |
| C3 | Fixed | security | |
| C4 | Fixed | security | |
| C5 | Fixed | security | |
| C6 | Fixed | security | |
| C7 | Fixed | core | |
| C8 | Fixed | ui | |
| C9 | Fixed | core | |
| C10 | Fixed | core | |
| C11 | Fixed | security | |
| C12 | Fixed | security | |
| C13 | Fixed | ui | |
| C14 | Fixed | core | |
| C15 | Fixed | ui | |
| C16 | Not changed (accepted risk) | — | The temporary password sits in the admin's own session row only between the redirect and the next page load, and is removed when shown. Anyone who can read the `sessions` table can already reset passwords. Not worth a more complex flow. |
| C17 | Fixed | security | |
| C18 | Fixed | ui | |
| C19 | Fixed | ui | |
| C20 | Fixed | core | |
| C21 | Fixed | security | |
| C22 | Fixed | core | |
| C23 | Fixed | security | |
| C24 | Fixed | core | |
| F1 | Fixed | ui | |
| F2 | Fixed | ui | |
| F3 | Fixed | ui | |
| F4 | Fixed | ui | |
| F5 | Fixed | ui | |
| F6 | Fixed | ui | |
| F7 | Fixed | ui | |
| F8 | Fixed | ui | |
| F9 | Fixed | ui | |
| F10 | Fixed | ui | |
| F11 | Fixed | ui | |
| F12 | Fixed | ui | |
| F13 | Fixed | ui | |
| D1 | Fixed | docs | |
| D2 | Fixed | docs | |
| D3 | Fixed | deliverables | |
| D4 | Fixed | docs | |
| D5 | Resolved by F3 | — | The fallback banner was restored, so the docs describing it are accurate again. |
| D6 | Fixed | docs | |
| D7 | Fixed | docs | |
| D8 | Fixed | docs | |
| D9 | Fixed | docs | |
| D10 | Fixed | docs | |
| D11 | Fixed | docs | |
| D12 | Fixed | docs | |
| D13 | Fixed | docs | |
| D14 | Fixed | docs | |
| D15 | Resolved by C6 | — | `schema.sql` is now idempotent, so the recovery step works. |
| D16 | Fixed | docs | |
| D17 | Fixed | docs | |
| D18 | Fixed | docs | |
| D19 | Fixed | docs | |
| D20 | Fixed | docs | |
| D21 | Fixed | docs | |
| D22 | No change needed (audit was wrong) | — | The branches *were* squashed before merging (`f44e1be` no longer exists; the stack became `5572167` + `9bbf23e`), then merged with a merge commit. The docs are accurate. |
| D23 | Fixed | docs | |
| D24 | Fixed | docs | |
| D25 | Fixed | docs | |

---

## 1. Code defects

### High

| ID | Issue | Where | Evidence |
|---|---|---|---|
| C1 | **Every Explore search and every admin user search crashes on MySQL.** The query is written as `ESCAPE '\\'` inside a PHP double-quoted string, so the SQL becomes `ESCAPE '\'`. MySQL reads the backslash as escaping the closing quote, which is a syntax error (1064). The page stops right after `<main>`, so the user sees a blank page with no error message. The tests miss it because they run on SQLite, where `'\'` is valid. | `classes/User.php:278,300,362` · `classes/Post.php:432,459` · used by `explore.php:45`, `admin.php:53` | **Confirmed**: `explore.php?q=hackathon` returns a truncated page, and the server log shows `PDOException … User.php line 370`. **Fix:** use `ESCAPE '!'` (and escape `!` in the input), and catch `PDOException` so the page shows an error state. |

### Medium

| ID | Issue | Where | Evidence |
|---|---|---|---|
| C2 | **Times are 5 hours off whenever the database clock is UTC.** PHP is pinned to `Asia/Karachi`, but SQLite's `CURRENT_TIMESTAMP` is always UTC (as is a MySQL server running in UTC), so a brand-new post shows "5h ago". This affects the advertised zero-config SQLite mode and any cloud deployment. | `includes/header.php:17` · `Post::formatTimeAgo` | **Confirmed** with a script: SQLite now → `5h ago`. **Fix:** store timestamps in UTC and convert them for display, or set the DB session time zone to match PHP. |
| C3 | **`post_action.php` leaks a PHP warning containing the server's absolute file path.** `$action` is only defined inside the valid-POST branch. A GET request or a failed CSRF check reaches line 71 with the variable undefined. `display_errors` is turned off only in `header.php`, which action handlers never include. | `post_action.php:71` | **Confirmed**: `Warning: Undefined variable $action in /Users/…/post_action.php on line 71`. If `output_buffering=0`, this would also break the redirect. |
| C4 | **"Strict session mode" has no effect.** `DbSessionHandler::read()` returns `''` for unknown IDs and there is no `validateId()`, so PHP accepts any session ID a client invents and stores a row for it. Login does regenerate the ID, so session fixation is still prevented, but the code comment claims protection that doesn't exist, and anyone can fill the `sessions` table with junk rows. | `classes/DbSessionHandler.php:37` · `classes/Auth.php:26` | **Confirmed**: a cookie `PHPSESSID=attackerchosen…` was accepted and inserted. **Fix:** implement `SessionUpdateTimestampHandlerInterface` (`validateId`, `updateTimestamp`). |
| C5 | **Internal files are served over HTTP.** The documented `php -S` mode ignores `.htaccess`, so `db/seed.sql` (password hashes and emails), `db/schema.sql`, `docs/` and `.git/config` are downloadable. `tests/run_all.php` also **runs the whole test suite (7 `exec()` processes) for any anonymous visitor**. On Apache, `.htaccess` does not block `.git/` or `includes/`. | `.htaccess:18` · `tests/run_all.php:24` | **Confirmed** with curl (status 200 on all of these). **Fix:** add `.git` and `includes` to the RewriteRule, add a CLI-only guard to `tests/*.php`, and add a router script for `php -S`. |
| C6 | **`db/schema.sql` cannot be imported twice.** Tables use `IF NOT EXISTS` but the indexes don't, so a second import stops with `ERROR 1061 Duplicate key name`. The `CREATE DATABASE` line also sets no charset, so on XAMPP's MariaDB (default `latin1`) emoji and Urdu text break if the user skips the manual step in the docs. | `db/schema.sql:1,29-30,42…` | **Confirmed**: the re-import failed with 1061. |
| C7 | **Half of the role permission table is never enforced.** `post.create`, `post.edit_own`, `post.delete_own`, `reply.create`, `reply.delete_own` and `announcement.delete_any` are never checked anywhere. Removing one from a role in the table would change nothing. | `classes/Authorization.php:53-66` | **Code**: grep finds 0 uses outside the class. |
| C8 | **The admin confirmation prompt can be switched off by a user's name.** The prompt text is built with `addslashes`, which doesn't escape newlines. A name containing a newline (possible through a crafted profile POST, because `updateProfile` only trims the ends) produces a JS syntax error, and the admin's Update and Reset buttons then submit with no confirmation. | `admin.php:135,146` · `classes/User.php:234` | **Code**: the generated markup was reproduced with the PHP CLI. **Fix:** build the JS string with `json_encode` and strip control characters from names. |
| C9 | **The personalized feed cache achieves very little.** Each user's feed is cached for 5 minutes, but every like, bookmark, follow, reply or post anywhere deletes **every** user's cached feed. With Redis, each like also scans the whole keyspace. Profile renames and role changes do *not* clear the cache, so names and badges can stay stale for 5 minutes. | `classes/Post.php:361-396` · `like_action.php:53` · `classes/Bookmark.php:49,75` · `profile_action.php` | **Code**. |
| C10 | **Repeated notifications.** Like → unlike → like, or follow → unfollow → follow, creates a new notification every time, so it is easy to flood someone. | `classes/Like.php:63` · `classes/Follow.php:54` | **Code**. **Fix:** skip the insert when an identical notification (same actor, type and reference) already exists. |

### Low

| ID | Issue | Where |
|---|---|---|
| C11 | Emails are stored exactly as typed. On SQLite, `Ali@nu.edu.pk` and `ali@nu.edu.pk` can both register, and login is case-sensitive. The rate limiter lowercases emails but the login lookup doesn't. | `User.php:57,192` |
| C12 | Login timing reveals whether an email is registered: `password_verify` is skipped for unknown emails. | `User.php:197` |
| C13 | Feed rows still select `author_email` and show the part before the `@` as a handle. That partly undoes the Sprint 5 "don't expose emails" fix (H5). | `Post.php:179,369` · `index.php:187` · `social_widgets.php:205` |
| C14 | The feed, explore and profile pages each load up to 500 full bookmarked posts (with sub-queries) just to build a list of IDs, although each feed row already carries `is_bookmarked`. A user with more than 500 bookmarks sees "Save" on posts they already saved. | `index.php:27` · `explore.php:66` · `profile.php:67` |
| C15 | Several lists have a hard cap and no pagination, while the counts shown are the full totals: replies 100, notifications 100, followers/following 100, bookmarks 50, liked posts 50. | `post.php:29` · `notifications.php:30` · `profile.php:45,62` · `bookmarks.php:26` |
| C16 | The admin's temporary password goes into the session flash message, so it sits in plaintext in `sessions.data` until it is displayed. | `admin_action.php:81` |
| C17 | Cache: keys have no namespace, `flush()` runs `FLUSHDB` (which wipes a shared Redis database), and `unserialize()` is called without `allowed_classes`. | `Cache.php:211,341` |
| C18 | Duplicated logic: `Notification::formatTimeAgo` copies `Post::formatTimeAgo`. `signup.php` re-implements the password policy without the 128-character cap, so a long password gets the generic "Registration failed" message. | `Notification.php:199` · `signup.php:24` |
| C19 | `signup.php` doesn't redirect users who are already logged in (`login.php` does). | `signup.php` |
| C20 | Notifications for deleted posts remain and link to a missing page. `reference_id` has no foreign key. | `schema.sql:116` |
| C21 | Rate limiter: two simultaneous first failures race on the INSERT, and the uncaught primary-key error returns a 500. Keying on email+IP also means trying one password against many emails (password spraying) is unlimited. | `LoginRateLimiter.php:105` |
| C22 | `idx_hashtags_tag` is redundant because the UNIQUE constraint already indexes `tag`. "Trending" counts all time, with no recent-time window. | `schema.sql:100` · `Hashtag.php:122` |
| C23 | `RbacTest` prints "headers already sent" warnings because `Auth::logout` sets a cookie after test output has started. | `tests/RbacTest.php:27` |
| C24 | Every logged-in page runs the unread-notification count query twice. | `header.php:4165` · `social_widgets.php:18` |

---

## 2. Feature and UI defects

| ID | Sev | Issue | Where |
|---|---|---|---|
| F1 | Med | **Paging past the end shows a misleading empty state.** `index.php?page=999` says "No posts yet… be the first", and a profile with posts says "You have not posted anything yet". There is no Previous link because the pager only renders when there are results. Explore handles this correctly. | `index.php:157-166` · `profile.php:196` |
| F2 | Med | **Clicking a notification may not mark it as read.** The `fetch` runs from an `onclick` on a link, and the navigation can cancel it, leaving the badge count wrong. | `notifications.php:102` (use `keepalive: true` or `sendBeacon`) |
| F3 | Med | **The SQLite fallback warning banner was removed.** `$isDbFallback` is still computed but never shown, so if MySQL is down the app silently switches to a different, empty database and logins fail with no explanation. The demo script still relies on the banner. | `includes/header.php:34-41` (banner removed in `8f49b57`) |
| F4 | Low | Deleting or editing a post always sends you back to the feed, even from the profile, explore, bookmarks or **admin panel** (admins are thrown out of moderation after every delete). | `post_action.php:71-77` · `admin.php:200` |
| F5 | Low | Saved posts don't look saved when the page loads: the server never adds the `is-bookmarked` class or attribute, and only the JS adds it after a click. | `social_widgets.php:249` · `index.php:228` · `post.php:105` |
| F6 | Low | The bookmarks page drops the Announcement badge and the @handle, because the query doesn't select `is_announcement` or `author_email`. | `Bookmark.php:117` |
| F7 | Low | Counts go stale after AJAX actions: the "Your Bookmarks (N)" heading, the Follower/Followers label, and your own Following count after following someone from a modal. | `bookmarks.php:39` · `profile.php:162,167` |
| F8 | Low | Explore's placeholder says it searches "#tags", but `q=#campus` runs a text search instead of the tag filter. | `explore.php:19-23,85` |
| F9 | Low | On the post page, logged-out users see no like or reply counts (the feed shows them). Reply avatars ignore the author's role, so official accounts get the wrong colour. | `post.php:100,195` |
| F10 | Low | Not-found pages return HTTP 200. `profile.php` with no ID while logged out shows "User not found" instead of redirecting to login. The landing page's Terms and Privacy links go to `#`. | `profile.php` · `index.php:329` |
| F11 | Low | Failed AJAX calls (expired session, server error) are ignored silently, with no message to the user. | `includes/footer.php` |
| F12 | Low | **Accessibility:** compose, reply and edit textareas have no label. The modal close buttons are a bare "×" with no accessible name. Modals have no `role="dialog"` and don't move or trap focus. Like buttons have no `aria-pressed` and a fixed label that hides the count. The nav `aria-label` hides the unread badge. | `index.php:72,236` · `post.php:126,154` · `profile.php:237,267,298` · `social_widgets.php:41,243` |
| F13 | Low | Minor inconsistencies: `benchmark.php` checks `role === 'system_admin'` directly instead of `Auth::can()`, and `post.php:109` has a redundant `$isOwner \|\|` check. | `benchmark.php:44` · `post.php:109` |

---

## 3. Documentation and Agile-artifact issues

### High (would mislead an evaluator or break setup)

| ID | Issue | Where |
|---|---|---|
| D1 | **The upgrade steps leave out the Sprint 5 RBAC migration.** Without `migrate_sprint5_rbac_mysql.sql`, an older database keeps the old role ENUM and has no `posts.is_announcement` column, so posting and every feed break. The admin manual also says "10 tables"; there are 11. | `docs/ADMIN_MANUAL.md:149-156` · `docs/DEPLOYMENT.md:42` |
| D2 | **Stale test counts.** DEPLOYMENT says "181/181" with old per-suite numbers and no RbacTest. The real total is **286** (UserTest 24, PostTest 29, SocialTest 52, NotificationTest 28, SessionTest 9, Sprint4Test 52, RbacTest 92). The README already says 286, so the docs contradict each other. | `docs/DEPLOYMENT.md:131-138` |
| D3 | **The final deliverables are stale.** `AIR_Report.pdf`, `AIR_Presentation.pptx` and their generator scripts say 181 tests and commit counts of 27/7/4 (actual: 44/13/5). The deck says RBAC, CSRF and rate limiting are "in progress", though they are merged. The velocity chart ends at 153 instead of 286. DEMO_SCRIPT tells evaluators to use these files "for the numbers". | `scripts/generate_report.py:71,115,489-494,597-600,634` · `generate_pptx.py:111-113` · `generate_figures.py:56` |
| D4 | **The zero-config SQLite mode has no demo accounts.** The docs say everything initializes automatically, but only empty tables are created, so none of the listed logins work. The only seeding tool (`php db/reseed_demo.php --confirm`) isn't documented anywhere. | `docs/DEPLOYMENT.md:65-73` |
| D5 | **The docs describe a fallback banner that no longer exists** (see F3), and the troubleshooting steps depend on it. | `docs/DEMO_SCRIPT.md:16-19,109,112` · `sprint-5-srs-increment.md:155` |
| D6 | **The README has 3 broken links**: `docs/sprint-3/4/5-review-retro.md` never existed. Those retros live inside `sprint-1-2-review-retro.md`. | `README.md:20` |
| D7 | **The user manual's example email would be rejected.** `student@campus.edu.pk` fails because only `@nu.edu.pk` is accepted. The Sprint 5 SRS also says "Any email address can register", which is wrong. | `docs/USER_MANUAL.md:46` · `sprint-5-srs-increment.md:144` |
| D8 | **MEETINGS.md contradicts the retro.** MEETINGS lists 5 meetings with attendees, while the retro says "no standup/meeting minutes were kept… none are invented here". An evaluator reading both will notice. | `docs/MEETINGS.md:3` · `sprint-1-2-review-retro.md:3-5` |

### Medium

| ID | Issue | Where |
|---|---|---|
| D9 | The admin manual says the announcement toggle is "in progress", which contradicts its own lines 32 and 45 (the feature is live). It also describes a "priority distribution in Explore" that doesn't exist. | `docs/ADMIN_MANUAL.md:57-61` |
| D10 | The docs say Home shows only you plus the people you follow. It actually defaults to the global "For you" feed; the personalized feed is the `?tab=following` tab. | `docs/USER_MANUAL.md:119,197` · `DEPLOYMENT.md:116` |
| D11 | The user manual still describes the browser `confirm()` delete prompt, which was replaced by the in-app modal in PR #14. | `docs/USER_MANUAL.md:111` |
| D12 | **CONTRIBUTION.md's git audit is stale:**<br>- it says 34 commits (actual 62) and counts of 27/7/4 (actual 44/13/5);<br>- Qaim's work is listed as "pending merge" (it was merged in PR #10);<br>- it cites commit `748f65d`, which doesn't exist;<br>- PRs #10–#14 are missing;<br>- it credits "Muse", who is not a git author. | `docs/CONTRIBUTION.md:3,11-13,103-106,116-156` |
| D13 | The UML class diagram claims "nothing here is aspirational" but is missing the admin, RBAC and CSRF methods (`listUsers`, `setRole`, `can`, `destroySessionsForUser`, `isFallback`…). It also has wrong signatures (the announcement parameter, `canDeleteAny`, `bypassCache`, static vs instance methods) and lacks the `PostController → Authorization` link. The ERD itself matches the schema. | `docs/uml-class-diagram-erd.md` |
| D14 | ADMIN_MANUAL says a like updates the counter without clearing the feed cache, so counts can be up to 300s stale. The Sprint 4 SRS says the opposite. In fact `like_action.php:53` clears all feed caches. | `ADMIN_MANUAL.md:141` · `sprint-4-srs-increment.md:48` |
| D15 | The demo script's recovery step ("re-import schema.sql + seed.sql") fails on an existing database (see C6). | `docs/DEMO_SCRIPT.md:112` |
| D16 | The Sprint 5 close checklist still says "UI polish branch merged (pending)", but it merged as PR #12, followed by #13 and #14. Sprint 2's "docs approved" box is unticked, though it merged as PR #3. | `sprint-1-2-review-retro.md:99,364-368` |

### Low

| ID | Issue | Where |
|---|---|---|
| D17 | Seed names are wrong ("ACM Chapter" is "ACM NUCES Chapter", "Campus Admin" is "Campus Administrator", "IEEE Branch" is "IEEE Student Branch"), and IEEE's role is listed as "Society" instead of Society Admin. There's also a broken `file:///config/Database.php` link, and the benchmark is presented as public although it is admin-only. | `docs/DEPLOYMENT.md:51,84-89,148` |
| D18 | The Likes tab is described as visible on other people's profiles. It is owner-only now. | `DEPLOYMENT.md:106` · `sprint-4-srs:29` |
| D19 | UI labels in the docs don't match the app: "Update role" is "Update", "Post as announcement" is "Announcement", "Memory (Fallback)" is "MemoryFallback". Example emails use `@campus.edu.pk`. | `ADMIN_MANUAL.md:82,94,99,136` · `DEMO_SCRIPT.md:68` |
| D20 | The WBS mapping is titled "(Sprints 1–3)" but covers Sprints 1–5. SocialTest is listed as 49 (it's 52). It says "92 RBAC tests at PR #11" but there were 91 at that commit. | `docs/wbs-mapping.md:1,77,106` · `sprint-5-srs:14` |
| D21 | Sprint dates contradict each other. Sprint 3 ends 6 Nov in CONTRIBUTION but "runs to 8 Nov" in the retro, overlapping Sprint 4. Sprint 5 is 13–15 Nov in the SRS but 12–14 Nov in the retro. | `CONTRIBUTION.md:28,57` · `retro:125,254` · `sprint-5-srs:3` |
| D22 | The docs say Sprint 3 PRs were squash-merged, but git shows normal two-parent merges. | `retro:103` · `sprint-3-srs:215` |
| D23 | The Sprint 5 SRS says the seed includes "FAST Computing Society… departmental faculty"; it doesn't. | `sprint-5-srs-increment.md:93` |
| D24 | The retro contains an unrelated lesson about "PHP and OpenMPI… `~/workspace/tools/`" that has nothing to do with this project. | `sprint-1-2-review-retro.md:354-356` |
| D25 | The N+1 audit says "the table above", but the table is below. | `docs/n-plus-1-audit-sprint4.md:15` |

---

## 4. Checked and found correct

- **XSS:** no holes found. All output is escaped and the JS uses `textContent`.
- **CSRF:** a token is present on every form and every AJAX POST, including login, signup and logout.
- **Database queries:** prepared statements are used everywhere (no SQL injection found).
- **Passwords:** stored as Argon2id hashes, and the session ID is regenerated at login.
- **Page access:** all page auth gates are correct, and `benchmark.php` is admin-only.
- **Rate limiting:** 5 failed attempts per 15 minutes, as documented. The 30-minute idle timeout works.
- **Tests:** 286/286 pass.
- **Docs:** PR numbers and merge hashes for #1–#11 are correct, the seed credentials are correct, and the ERD matches `schema.sql`.

## 5. Suggested fix order (safe, smallest first)

1. **C1:** fix the search crash. It's a one-line change in 5 places. Add a MySQL-specific regression test, or at least a smoke test.
2. **C3, C6, C5:** guard `$action`, make the schema re-runnable and set its charset, and block `.git`, `includes` and `tests` from the web.
3. **C2:** fix the timezone handling.
4. **F1, F3, F4:** restore the fallback banner, fix the pager on empty pages, and fix the return redirects.
5. **D1–D8:** fix the high-severity docs, then regenerate the report, deck and figures from current main.
6. Everything else, as time allows.

Each fix should go on a `feature/…` branch, and `php tests/run_all.php` must stay at 286/286 green before merging.
