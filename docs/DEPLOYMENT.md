# CampusConnect — Deployment & Quickstart Guide (Problem 12)

A centralized University Microblogging & Announcement Hub built using PHP 8 (OOP) and MySQL.
This guide enables a complete, working environment setup on a fresh machine in **under 5 minutes**.

---

## 1. Prerequisites

- **XAMPP / WampServer / LAMP** with:
  - **PHP 8.1+** (extensions enabled: `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`).
  - **MySQL 5.7+** or **MariaDB 10.4+**.
  - **Apache Web Server**.
- Optional: Redis Server (port `6379`) for caching performance comparisons. *(CampusConnect includes a transparent zero-downtime memory/MySQL fallback if Redis is not installed).*

---

## 2. Fast 5-Minute Setup (XAMPP)

### Step 1: Copy Project to Web Directory
Place or clone the project repository directly inside your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\campusconnect
```

### Step 2: Start Services
Open the **XAMPP Control Panel** and click **Start** for both **Apache** and **MySQL**.

### Step 3: Create Database & Import Schemas
Open your browser and navigate to **phpMyAdmin** at [http://localhost/phpmyadmin](http://localhost/phpmyadmin), or use the MySQL terminal:

1. Create a database named:
   ```sql
   CREATE DATABASE campusconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Import the core schema:
   - Select `campusconnect` database &rarr; click **Import** tab.
   - Choose file: `db/schema.sql` &rarr; click **Go**. (This already includes the Sprint 5 `notifications` table — no separate migration needed on a fresh install.)
3. Import the university seed data:
   - Choose file: `db/seed.sql` &rarr; click **Go**.

> **Existing databases only:** if your `campusconnect` database was created before Sprint 5, run these once, in order: `db/migrate_sprint5_notifications_mysql.sql` (notifications table), `db/migrate_sprint5_rbac_mysql.sql` (new role names, `posts.is_announcement`, `login_attempts` — without it posting and the feed break), and `db/migrate_6_reports.sql` (reports table). Do not run them on a fresh install — `schema.sql` already has everything. The full sequence is in `docs/ADMIN_MANUAL.md` §5.1.

*(Or via command line in 5 seconds):*
```bash
mysql -u root -p campusconnect < db/schema.sql
mysql -u root -p campusconnect < db/seed.sql
```

### Step 4: Verify Database Configuration
Check [`config/Database.php`](../config/Database.php) to ensure credentials match your local MySQL server (default XAMPP credentials work out-of-the-box):
```php
private $host = "localhost";
private $db   = "campusconnect";
private $user = "root";
private $pass = "";
```

### Step 5: Open in Browser
Visit the application in your browser:
👉 **[http://localhost/campusconnect](http://localhost/campusconnect)**

---

## 3. Alternative: Instant Zero-Config Run (Built-in Server)

You can run CampusConnect **without XAMPP or MySQL** using the embedded PHP development server and the built-in SQLite database engine:

```bash
cd campusconnect
php -S localhost:8000 router.php
```

> Always pass `router.php`: the built-in server ignores `.htaccess`, and the router is what keeps `db/`, `docs/`, `tests/`, `includes/` and `.git/` from being served to the browser.
Open **[http://localhost:8000](http://localhost:8000)** in your browser. The tables are created automatically in `db/campusconnect.sqlite`, but they start **empty** — `db/seed.sql` uses MySQL-only syntax and cannot load into SQLite, so none of the demo logins in §4 exist yet. To load the same 7 demo accounts and 8 posts into the SQLite database, run once:

```bash
php db/reseed_demo.php --confirm
```

> ⚠️ `reseed_demo.php` wipes **all** data in whichever database the app connects to. It tries MySQL first, so only run it for SQLite while MySQL is stopped — otherwise it re-seeds your MySQL database instead. (The test suites never use this file: they run on their own throwaway SQLite database.)

---

## 4. Demo User Accounts & Credentials

The seed data (`db/seed.sql`) pre-configures realistic accounts representing all university roles:

| Role | Name | Email | Password | Department / Description |
|---|---|---|---|---|
| **Student** | Ali Ahmed | `student@nu.edu.pk` | `StrongPass123!` | CS Junior; active poster, has followers, likes, & bookmarks |
| **Society Admin** | ACM NUCES Chapter | `society@nu.edu.pk` | `StrongPass123!` | Official ACM Student Chapter; posts hackathon announcements |
| **System Admin** | Campus Administrator | `admin@nu.edu.pk` | `StrongPass123!` | Central University Administrator; posts official notices & moderates |
| **Student** | Zainab Malik | `zainab@nu.edu.pk` | `StrongPass123!` | AI researcher; published author; followers & threads |
| **Student** | Bilal Khan | `bilal@nu.edu.pk` | `StrongPass123!` | Competitive programmer; replies & team discussions |
| **Student** | Sara Tariq | `sara@nu.edu.pk` | `StrongPass123!` | Robotics sophomore; hardware discussions |
| **Society Admin** | IEEE Student Branch | `ieee@nu.edu.pk` | `StrongPass123!` | IEEE FAST Student Branch; workshops & seminars |

*Note: All accounts share the password `StrongPass123!` for easy grading and live evaluation.*

---

## 5. Evaluator Feature Checklist (What to Test)

1. **Authentication & Session Security**:
   - Log in with `student@nu.edu.pk`.
   - Notice session security with database-backed persistence (`sessions` table).
2. **Timeline Feed & Microblogging Engine**:
   - Compose a 280-character post with `#hashtags`.
   - Live character counter (`n / 280`) and auto-expanding textarea.
   - Delete controls available on your own posts; administrative moderation on admin login.
3. **Likes & Heart Reactions (WBS 4.1)**:
   - Click the heart icon on any post: instant AJAX reaction with live counter update.
   - Open your own profile: the **"Likes" tab** lists every post you liked (it is private — other users' profiles do not show it).
4. **Hashtags & Discovery (WBS 4.2)**:
   - Click any `#tag` link in post text (e.g., `#Hackathon2026`, `#FASTNUCES`) to filter posts.
   - Check the **Trending Topics** widget in the right rail.
5. **Notifications Center & Bell Badge (WBS 5.x)**:
   - Observe the bell icon in the top navbar displaying unread count badges (badge refreshes on page load).
   - Click bell to navigate to `notifications.php` (shows follower, like, and reply alerts).
   - Click "Mark all as read" or click individual notifications to clear unread badges.
6. **Social Graph**:
   - Follow / unfollow users from the right rail "Who to follow" widget or user profiles.
   - Switch the home feed between **For you** (all posts, the default) and **Following** (you + people you follow).
7. **Threaded Replies & Saved Bookmarks**:
   - Click on any post to open detail view with threaded comments.
   - Bookmark posts and view them anytime on `bookmarks.php`.
8. **Profile Customization**:
   - Visit `profile.php`: choose from 6 whitelisted cover styles (`signature`, `cobalt`, `teal`, `sunset`, `violet`, `graphite`).

---

## 6. Automated Unit Testing & Quality Assurance

CampusConnect includes comprehensive automated test suites covering all modules:

```bash
# Run every suite at once (exit code 0 = all green):
php tests/run_all.php

# Or one suite at a time:
php tests/UserTest.php          #  31 tests: auth, password policy, email case, SQLi immunity
php tests/PostTest.php          #  49 tests: post CRUD, validation, soft-delete, pagination, time zones, safe redirects
php tests/SocialTest.php        #  62 tests: follows, personalized feed, replies, bookmarks, search, pagers
php tests/NotificationTest.php  #  35 tests: notifications, unread counts, triggers, dedupe
php tests/SessionTest.php       #  13 tests: DB-backed session handler, GC, strict-mode IDs
php tests/Sprint4Test.php       #  68 tests: likes, hashtags/trending, cache (live counts, invalidation)
php tests/RbacTest.php          # 104 tests: roles/permissions, CSRF, rate limiting, announcements
php tests/ReportTest.php        #  36 tests: post reports and the moderation queue
php tests/MysqlCompatTest.php   #  14 tests: real MySQL (temporary DB); skipped when MySQL is down
```
**Total: 412 tests (398 + 14 MySQL-only), all passing on 15 Nov 2026.** Every suite except `MysqlCompatTest` runs on its own throwaway SQLite database, so no server is needed.

---

## 7. Performance Benchmarking

To demonstrate the Redis / in-memory caching performance gain versus direct database queries, run:
```bash
php benchmark.php
```
Or view in browser at: `http://localhost/campusconnect/benchmark.php` — **system admins only** (log in as `admin@nu.edu.pk`); other users get 403 and logged-out visitors are sent to login.
Displays execution latency and visual SVG performance comparison graphs.
