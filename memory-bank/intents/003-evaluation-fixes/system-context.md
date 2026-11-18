---
intent: 003-evaluation-fixes
phase: inception
status: draft
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:00:00Z
---

# System Context: Evaluation Fixes (Intent 003)

## System Boundaries
This intent refactors the existing **CampusConnect** monolithic PHP application and tightens its database constraints. No new features, no new tables, no new user roles. The architecture stays the same; what changes is *where* code lives (controllers, base class, handler chain) and *how strictly* the database enforces its own rules.

```text
+-----------------------------------------------------------------------------------+
|                                CampusConnect Core                                 |
|                                                                                   |
|  +--------------------+   +--------------------+   +---------------------------+  |
|  | Web Presentation   |   | Domain Logic       |   | Storage & Performance     |  |
|  | - index.php        |   | - Model (base)*    |   | - MySQL 8/9 / SQLite      |  |
|  | - login.php        |   | - User, Post, Auth |   | - reports: ENUM/CHECK*    |  |
|  | - profile.php      |   | - Controllers*     |   | - notifications: FK*      |  |
|  | - explore.php      |   | - Cache chain*     |   | - SQLite: generated*      |  |
|  | - admin.php        |   | - Helpers (obj)*   |   | - TinyRedis / Redis 5     |  |
|  | - notifications.php|   | - PHPUnit (opt)*   |   | - File system caching     |  |
|  +--------------------+   +--------------------+   +---------------------------+  |
+-----------------------------------------------------------------------------------+
  * Indicates a component changed or introduced by Intent 003
```

## What Changes, by Layer
1. **Presentation:** `*_action.php` pages become thin entry points delegating to controllers (units 003–005). No URL or UI changes.
2. **Domain:** a `Model` base class (unit 001), a real Chain of Responsibility for cache drivers (unit 002), injectable helper objects (unit 006), and optionally PHPUnit (unit 007).
3. **Storage:** ENUM/CHECK constraints on `reports` (unit 008), a safe FK on `notifications.reference_id` (unit 009), and a generated SQLite schema (unit 010).

## User Roles & Boundaries
Unchanged from the delivered product: Student (`student`), Society Admin (`society_admin`), Campus Administrator (`system_admin`). No role or permission changes in this intent — RBAC stays enforced (covered by RbacTest).

## Relationship to Intent 002
Intent 002 (9 bug fixes) runs in parallel with the same 9 team members in the same window (18–23 Nov 2026). The two intents touch different files except `classes/Notification.php` (intent 002 unit 003 adds notifications in `Report::review()`; intent 003 unit 009 constrains `reference_id`) — coordinate that overlap in review. If anything slips, intent 003 Musts take priority: they are what Sir re-evaluates on 24 Nov 2026.
