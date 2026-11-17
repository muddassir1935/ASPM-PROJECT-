---
intent: 002-quality-enhancements
phase: inception
status: draft
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# System Context: Quality Enhancements (Intent 002)

## System Boundaries
This intent extends the existing **CampusConnect** monolithic PHP architecture without breaking existing database schemas or Redis fallback primitives.

```text
+-----------------------------------------------------------------------------------+
|                                CampusConnect Core                                 |
|                                                                                   |
|  +--------------------+   +--------------------+   +---------------------------+  |
|  | Web Presentation   |   | Domain Logic       |   | Storage & Performance     |  |
|  | - index.php        |   | - User, Post, Auth |   | - MySQL 8/9 / SQLite      |  |
|  | - messages.php*    |   | - Media*, Poll*    |   | - TinyRedis / Redis 5     |  |
|  | - explore.php      |   | - DirectMessage*   |   | - uploads/posts/ storage* |  |
|  | - notifications.php|   | - AutoModerator*   |   | - File system caching     |  |
|  +--------------------+   +--------------------+   +---------------------------+  |
+-----------------------------------------------------------------------------------+
  * Indicates newly introduced or upgraded component in Intent 002
```

## User Roles & Boundaries
1. **Student (`student`)**: Can author text/image posts, vote in campus polls, send direct messages to peers, filter search, and toggle persistent theme.
2. **Society Admin (`society_admin`)**: All student abilities plus official society announcements and society event polls.
3. **Campus Administrator (`system_admin`)**: All abilities plus reviewing manual & auto-moderated reports, managing user roles, and resetting credentials.
4. **System Auto-Moderator (`system_auto_mod`)**: Automated background heuristic filter flagging policy violations.
