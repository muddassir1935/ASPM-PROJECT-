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
   - Choose file: `db/schema.sql` &rarr; click **Go**.
3. Import the Sprint 5 Notifications migration:
   - Choose file: `db/migrate_sprint5_notifications_mysql.sql` &rarr; click **Go**.
4. Import the university seed data:
   - Choose file: `db/seed.sql` &rarr; click **Go**.

*(Or via command line in 5 seconds):*
```bash
mysql -u root -p campusconnect < db/schema.sql
mysql -u root -p campusconnect < db/migrate_sprint5_notifications_mysql.sql
mysql -u root -p campusconnect < db/seed.sql
```

### Step 4: Verify Database Configuration
Check [`config/Database.php`](file:///config/Database.php) to ensure credentials match your local MySQL server (default XAMPP credentials work out-of-the-box):
```php
private $host = "localhost";
private $db_name = "campusconnect";
private $username = "root";
private $password = "";
```

### Step 5: Open in Browser
Visit the application in your browser:
👉 **[http://localhost/campusconnect](http://localhost/campusconnect)**

---

## 3. Alternative: Instant Zero-Config Run (Built-in Server)

You can run CampusConnect **without XAMPP or MySQL** using the embedded PHP development server and the built-in SQLite database engine:

```bash
cd campusconnect
php -S localhost:8000
```
Open **[http://localhost:8000](http://localhost:8000)** in your browser. All tables, sessions, and tests initialize automatically in `db/campusconnect.sqlite`.

---

## 4. Demo User Accounts & Credentials

The seed data (`db/seed.sql`) pre-configures realistic accounts representing all university roles:

| Role | Name | Email | Password | Department / Description |
|---|---|---|---|---|
| **Student** | Ali Ahmed | `student@nu.edu.pk` | `StrongPass123!` | CS Junior; active poster, has followers, likes, & bookmarks |
| **Society Admin** | ACM Chapter | `society@nu.edu.pk` | `StrongPass123!` | Official ACM Student Chapter; posts hackathon announcements |
| **System Admin** | Campus Admin | `admin@nu.edu.pk` | `StrongPass123!` | Central University Administrator; posts official notices & moderates |
| **Student** | Zainab Malik | `zainab@nu.edu.pk` | `StrongPass123!` | AI researcher; published author; followers & threads |
| **Student** | Bilal Khan | `bilal@nu.edu.pk` | `StrongPass123!` | Competitive programmer; replies & team discussions |
| **Student** | Sara Tariq | `sara@nu.edu.pk` | `StrongPass123!` | Robotics sophomore; hardware discussions |
| **Society** | IEEE Branch | `ieee@nu.edu.pk` | `StrongPass123!` | IEEE FAST Student Branch; workshops & seminars |

*Note: All accounts share the password `StrongPass123!` for easy grading and live evaluation.*

---

## 5. Evaluator Feature Checklist (What to Test)

1. **Authentication & Session Security**:
   - Log in with `student@nu.edu.pk`.
   - Notice session security with database-backed persistence (`sessions` table).
2. **Timeline Feed & Microblogging Engine**:
   - Compose a 280-character post with `#hashtags`.
   - Live character counter (`n / 280`) and auto-expanding textarea.
   - Delete/edit controls available on your own posts; administrative moderation on admin login.
3. **Likes & Heart Reactions (WBS 4.1)**:
   - Click the heart icon on any post: instant AJAX reaction with live counter update.
   - Visit user profiles: check the dedicated **"Likes" tab** to see all liked posts.
4. **Hashtags & Discovery (WBS 4.2)**:
   - Click any `#tag` link in post text (e.g., `#Hackathon2026`, `#FASTNUCES`) to filter posts.
   - Check the **Trending Topics** widget in the right rail.
5. **Notifications Center & Live Bell Badge (WBS 5.x)**:
   - Observe the bell icon in the top navbar displaying unread count badges.
   - Click bell to navigate to `notifications.php` (shows follower, like, and reply alerts).
   - Click "Mark all as read" or click individual notifications to clear unread badges.
6. **Social Graph**:
   - Follow / unfollow users from the right rail "Who to follow" widget or user profiles.
   - Notice how the home feed automatically filters to personalized followed feeds.
7. **Threaded Replies & Saved Bookmarks**:
   - Click on any post to open detail view with threaded comments.
   - Bookmark posts and view them anytime on `bookmarks.php`.
8. **Profile Customization**:
   - Visit `profile.php`: choose from 6 whitelisted cover styles (`signature`, `cobalt`, `teal`, `sunset`, `violet`, `graphite`).

---

## 6. Automated Unit Testing & Quality Assurance

CampusConnect includes comprehensive automated test suites covering all modules:

```bash
# Run all unit test suites from the project root:
php tests/UserTest.php          # 18 tests: Auth, password policy, SQLi immunity
php tests/PostTest.php          # 29 tests: Post CRUD, validation, soft-delete, pagination
php tests/SocialTest.php        # 49 tests: Follows, personalized feed, replies, bookmarks
php tests/SessionTest.php       #  9 tests: DB-backed session handler & GC
php tests/Sprint4Test.php       # 48 tests: Likes, hashtags, cache invalidation, Redis fallback
php tests/NotificationTest.php   # 25 tests: Notifications model, unread counts, social triggers
```
**Total: 178 / 178 Tests Passing (100% Green)**.

---

## 7. Performance Benchmarking

To demonstrate the Redis / in-memory caching performance gain versus direct database queries, run:
```bash
php benchmark.php
```
Or view in browser at: `http://localhost/campusconnect/benchmark.php`.
Displays execution latency and visual SVG performance comparison graphs.
