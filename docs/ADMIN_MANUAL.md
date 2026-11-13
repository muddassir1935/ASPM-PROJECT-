# CampusConnect — Administrator & Operations Manual

This manual provides authoritative operational documentation for **Society Administrators** (`society_admin`) and **System Administrators** (`system_admin`) responsible for maintaining governance, security, moderation, and high availability on CampusConnect.

---

## Table of Contents
1. [Role Hierarchy & Permissions Architecture](#1-role-hierarchy--permissions-architecture)
2. [Society Administration (`society_admin`)](#2-society-administration-society_admin)
   - [Broadcasting Official Society Announcements](#21-broadcasting-official-society-announcements)
   - [Managing Society Profile Identity](#22-managing-society-profile-identity)
3. [System Administration (`system_admin`)](#3-system-administration-system_admin)
   - [Global Content Moderation](#31-global-content-moderation)
   - [User Role Governance](#32-user-role-governance)
   - [Database Session & Security Management](#33-database-session--security-management)
4. [Performance Monitoring & Caching Operations](#4-performance-monitoring--caching-operations)
   - [Interpreting the Benchmark Dashboard](#41-interpreting-the-benchmark-dashboard)
   - [Redis vs. Memory Fallback Diagnostics](#42-redis-vs-memory-fallback-diagnostics)
   - [Cache Invalidation Life-Cycle](#43-cache-invalidation-life-cycle)
5. [Database Migrations & Maintenance](#5-database-migrations--maintenance)
   - [Migration Sequence Guide](#51-migration-sequence-guide)
   - [Seed Data Ingestion](#52-seed-data-ingestion)
   - [Backup & Disaster Recovery Procedures](#53-backup--disaster-recovery-procedures)

---

## 1. Role Hierarchy & Permissions Architecture

CampusConnect implements an Agile Role-Based Access Control (RBAC) model configured in the database via the `users.role` enum/varchar column:

> **RBAC status (13 Nov 2026):** the role model, central permission map and 30-minute idle timeout are built on the `feature/sprint-5-rbac` branch (commit `748f65d`) and land in main with that branch (due 15 Nov). The admin panel, society-announcement toggle, CSRF tokens and login rate-limit are still in progress on the same branch. On main today the roles are still `student` / `society` / `admin`, and only admin delete of others' posts/replies is enforced.

| Privilege / Action | Student (`student`) | Society Admin (`society_admin`) | System Admin (`system_admin`) |
|---|:---:|:---:|:---:|
| Read Timelines & Explore | Granted | Granted | Granted |
| Compose Standard Posts | Granted | Granted | Granted |
| Publish Official Society Announcements | **Denied** | **Granted** | **Granted** |
| Edit & Soft-Delete Own Content | Granted | Granted | Granted |
| Soft-Delete Any User's Post (Moderation) | **Denied** | **Denied** | **Granted** |
| Soft-Delete Any User's Reply | **Denied** | **Denied** | **Granted** |
| View Admin Panel & User Role Editor | **Denied** | **Denied** | **Granted** |
| View Benchmark Dashboard (`benchmark.php`) | Granted | Granted | Granted |

*Note: the Admin Panel & User Role Editor and the Society Announcement toggle land with the `feature/sprint-5-rbac` branch (due 15 Nov); they are not in main yet.*

All authorization decisions execute server-side within the domain classes (`PostController`, `Post`, `Reply`, `User`, `Auth`) using strict PDO queries. Front-end hidden buttons are never treated as security boundaries.

---

## 2. Society Administration (`society_admin`)

Society Administrators represent officially registered student societies, student councils, and academic departments (e.g., Computing Society, Debating Club, Sports Directorate).

### 2.1 Broadcasting Official Society Announcements

> **In progress** — the announcement toggle lands with the `feature/sprint-5-rbac` branch (due 15 Nov). The flow below describes the planned behaviour once that branch merges.

1. When logged in as a `society_admin`, the compose form on `index.php` unlocks the **Society Announcement** toggle.
2. Composing an announcement marks the post with an official announcement badge and highlights the border with the university accent style.
3. Official announcements receive priority distribution in the Explore section and appear with high-visibility markers across student followers' feeds.
4. Use standard hashtags (e.g., `#IEEE_Recruitment`, `#SportsGala`, `#Orientation2026`) to maximize reach.

### 2.2 Managing Society Profile Identity
1. Maintain an up-to-date Society Bio in `profile.php`, including office hours, executive committee contacts, and meeting venues.
2. Select an official Society Cover style from the preset palette (e.g., Cobalt or Sunset) to ensure immediate visual recognition.

---

## 3. System Administration (`system_admin`)

System Administrators hold supreme technical and moderation authority across the application.

### 3.1 Global Content Moderation
CampusConnect operates a zero-tolerance policy for academic dishonesty, harassment, or Terms of Service violations:
- **Soft-Delete Any Post**: System admins can see a red **Delete Post** button on the post detail page (`post.php?id=X`). Clicking it sets `deleted_at = CURRENT_TIMESTAMP`, instantly hiding the post from all public and user feeds. (Feed cards have no admin delete button — moderation happens from the detail page.)
- **Soft-Delete Any Reply**: Admin accounts possess full privilege to moderate abusive replies on thread detail pages.
- **Audit Trails**: Soft-deleted records remain intact in the database for legal and academic disciplinary review. Soft-deleted content is never purged by normal application flows.

### 3.2 User Role Governance
To upgrade a student to a Society Admin or System Admin (there is no admin console yet — roles are changed by direct SQL):

> **Applies only after the RBAC migration.** The role values `society_admin` / `system_admin` exist only on the `feature/sprint-5-rbac` branch. On main today the role column is `ENUM('student','admin','society')` — use `'society'` / `'admin'` there instead.

```sql
-- Upgrading a verified society account (after the RBAC migration):
UPDATE users 
SET role = 'society_admin' 
WHERE email = 'computing.society@campus.edu.pk';

-- Appointing a platform administrator (after the RBAC migration):
UPDATE users 
SET role = 'system_admin' 
WHERE email = 'lead.admin@campus.edu.pk';
```
*Note: on main today a role change takes effect on the user's next login (the session role is not refreshed). After the RBAC merge, the role is refreshed on the next request.*

### 3.3 Database Session & Security Management
- Sessions are maintained in the `sessions` table by `DbSessionHandler`.
- Anti-Fixation & Anti-Hijacking: Sessions are regenerated upon every authentication state change.
- Automated Garbage Collection: Expired sessions are periodically pruned during `session_start()`. To run a manual purge:
  ```sql
  DELETE FROM sessions WHERE last_activity < (UNIX_TIMESTAMP() - 86400);
  ```

---

## 4. Performance Monitoring & Caching Operations

### 4.1 Interpreting the Benchmark Dashboard
Navigate to `benchmark.php` in your browser or run the CLI suite:
```bash
php benchmark.php
```
The dashboard runs comparative stress iterations of the timeline feed query (limit 20) measuring:
1. **Direct DB**: Average raw SQL query execution latency (ms).
2. **Cached**: Latency of responses served from the cache layer.
3. **Speedup Ratio**: Acceleration factor (measured **532×** in the Sprint 4 verification).

### 4.2 Redis vs. Memory Fallback Diagnostics
CampusConnect uses a multi-tier caching strategy (`classes/Cache.php`):
- **Tier 1 (Redis via phpredis / TinyRedisClient)**: Connects to Redis daemon (`127.0.0.1:6379`) with a 300ms connect timeout.
- **Tier 2 (In-Process Memory Fallback)**: If Redis is absent, down, or socket connectivity is refused, the cache automatically falls back to PHP in-memory runtime caching without throwing exceptions or degrading user uptime.
- To verify active cache backend:
  - Check the label on `benchmark.php`: "Redis (Active)" or "Memory (Fallback)".

### 4.3 Cache Invalidation Life-Cycle
The caching layer automatically maintains consistency:
- Mutations (`Post::create`, `Post::update`, `Post::delete`) invoke `Cache::getInstance()->deleteByPrefix('feed_')`.
- Engagement events (`Follow::toggle`, `Bookmark::toggle`) flush relevant cached timeline segments to prevent stale engagement counters. `Like::toggle` updates the cached like counter directly instead of flushing the feed — the anonymous/global feed `like_count` can sit stale up to the 300s TTL.

---

## 5. Database Migrations & Maintenance

### 5.1 Migration Sequence Guide

**Fresh install (new server, empty database): run `db/schema.sql` ONLY.** It already contains all 10 tables (users, posts, follows, replies, bookmarks, sessions, likes, hashtags, post_hashtags, notifications) with all indexes. Do NOT run any `migrate_*.sql` file afterwards — the tables and indexes already exist, so the migrations error out.

**Upgrading an existing database** created before a sprint: run only the migration(s) for the sprints newer than that database, in chronological order:

1. `db/migrate_sprint3_mysql.sql` — Follows, Bookmarks, Replies (only if the DB predates Sprint 3).
2. `db/migrate_cover_style_mysql.sql` — User profile cover style attribute (only if the DB predates it).
3. `db/migrate_sprint4_mysql.sql` — Likes, Hashtags, Post-Hashtags taxonomy (only if the DB predates Sprint 4).
4. `db/migrate_sprint5_notifications_mysql.sql` — In-app activity notifications (only if the DB predates Sprint 5).

### 5.2 Seed Data Ingestion
To populate realistic university test fixtures for accreditation demonstrations or staging (run after `db/schema.sql` on a fresh install):
```bash
mysql -u root -p campusconnect < db/seed.sql
```
This loads the verified demo accounts from `db/seed.sql` (all with password `StrongPass123!`):
- `admin@nu.edu.pk` — Campus Administrator (System Admin, role `admin`)
- `society@nu.edu.pk` — ACM NUCES Chapter (Society, role `society`)
- `ieee@nu.edu.pk` — IEEE Student Branch (Society, role `society`)
- `student@nu.edu.pk` — Ali Ahmed (Student)
- `zainab@nu.edu.pk` — Zainab Malik (Student)
- `bilal@nu.edu.pk` — Bilal Khan (Student)
- `sara@nu.edu.pk` — Sara Tariq (Student)

### 5.3 Backup & Disaster Recovery Procedures
Recommended nightly backup job:
```bash
# Dump full database including schema and data
mysqldump -u root -p --single-transaction --quick campusconnect > backup_$(date +%Y%m%d).sql
```
To restore from backup:
```bash
mysql -u root -p campusconnect < backup_20261113.sql
```
