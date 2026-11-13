# Sprint 5 — SRS Increment (Notifications, RBAC, Security Hardening & Responsive Polish)

> Status: Final Sprint increment for Sprint 5 (13–15 Nov 2026).
> Branch: `feature/sprint-5-docs` (Qaim) collaborating with `feature/sprint-5-notifications` (Faiz) and `feature/sprint-5-rbac` (Muddassir & Muse).
> Mapped to official WBS 5.0.

---

## 1. Purpose and Scope

Sprint 5 represents the final delivery and release hardening cycle for **CampusConnect**. Building upon the authentication (Sprint 1), core posting engine (Sprint 2), social discovery and design system (Sprint 3), and engagement caching layer (Sprint 4), Sprint 5 delivers the closing feature set and operational readiness:

1. **In-App Notifications System (WBS 5.1)**: Instant event-driven activity notifications for students and society leads (new followers, replies to posts, likes on posts) with unread notification badge counts and a dedicated notification hub.
2. **Role-Based Access Control & Society Management (WBS 5.2)**: Granular three-tier role taxonomy (`student`, `society_admin`, `system_admin`), central authorization matrix, official society announcement broadcasting, and administrative content moderation.
3. **Security Hardening (WBS 5.3)**: Anti-CSRF token lifecycle across all mutation endpoints, brute-force login rate limiting, session idle expiry, and XSS sanitization audit.
4. **Responsive Mobile Polish & Micro-interactions (WBS 5.4)**: Pixel-perfect layout optimization down to narrow mobile viewports (360px), tablet grid adjustments, tactile button feedback, and spring-based like heart animations.
5. **Turnkey Deployment & Demo Seed Data (WBS 5.5)**: Deterministic university database seed (`db/seed.sql`) containing diverse department users, posts, and society channels, paired with a rapid 5-minute XAMPP deployment guide (`docs/DEPLOYMENT.md`).

---

## 2. Functional Requirements

### FR-5.1: Notifications System (WBS 5.1)
- **Domain Model (`classes/Notification.php`)**:
  - `create(int $userId, string $type, int $referenceId, string $message): bool`: Inserts a notification record. Prevents self-notifications (e.g., author liking their own post).
  - `getUnread(int $userId, int $limit = 50): array`: Fetches unread notifications newest-first, enriched with sender/actor details.
  - `getAll(int $userId, int $limit = 50, int $offset = 0): array`: Paginated retrieval of historical notifications.
  - `getUnreadCount(int $userId): int`: Efficient query counting unread items (`is_read = 0`) to power header badges.
  - `markAsRead(int $notificationId, int $userId): bool`: Marks a specific notification read with strict user ownership validation.
  - `markAllAsRead(int $userId): bool`: Bulk updates all unread notifications for the user.
- **Triggers**:
  - *Follow*: When User A follows User B, notify User B (`type = 'follow'`).
  - *Reply*: When User A replies to User B's post, notify User B (`type = 'reply'`).
  - *Like*: When User A likes User B's post, notify User B (`type = 'like'`).
- **UI Affordances**:
  - Header notification bell icon displaying a dynamic unread badge with count indicator.
  - Dedicated notification center (`notifications.php`) displaying activity feed with direct deep-links to the originating post or user profile.

### FR-5.2: Role-Based Access Control (RBAC) & Society Hub (WBS 5.2)
- **Role Hierarchy**:
  - `student`: Standard university member. Can compose posts, comment, like, follow, bookmark, and manage own profile.
  - `society_admin`: Elected student society or departmental representative. Possesses all student privileges plus society announcement broadcasting and society profile badge privileges.
  - `system_admin`: Platform administrator. Possesses global content moderation authority (soft-deleting violating posts/replies across the platform, managing user roles, and accessing administrative dashboards).
- **Announcements**:
  - Dedicated society announcement styling and category tags distinguishing verified campus society dispatches from general student chatter.
- **Access Control Matrix**:
  | Action | Student | Society Admin | System Admin |
  |---|:---:|:---:|:---:|
  | Read Public Feeds & Explore | Yes | Yes | Yes |
  | Compose Standard Post | Yes | Yes | Yes |
  | Broadcast Society Announcement | No | Yes | Yes |
  | Edit / Soft-Delete Own Content | Yes | Yes | Yes |
  | Moderate / Soft-Delete Any Content | No | No | Yes |
  | Access System Administration Panel | No | No | Yes |

### FR-5.3: Security Hardening (WBS 5.3)
- **CSRF Token Validation**:
  - Synchronizer Token Pattern implemented across all POST forms (`post_action.php`, `reply_action.php`, `like_action.php`, `profile_action.php`, `bookmark_action.php`).
  - Cryptographically secure pseudo-random token (`bin2hex(random_bytes(32))`) stored in session and matched on incoming requests using `hash_equals()`.
- **Login Rate Limiting**:
  - Defends against credential stuffing and brute-force attacks by tracking failed authentication attempts per IP/Email within a sliding time window (5 failed attempts locks for 15 minutes).
- **Session Idle Timeout**:
  - Configurable inactivity timeout (e.g. 30 minutes of idle time automatically terminates session) to safeguard public laboratory terminals.

### FR-5.4: Responsive Polish & Micro-interactions (WBS 5.4)
- **Mobile Viewport Optimization (360px – 480px)**:
  - Header brand text and action buttons adapt dynamically without horizontal scroll or double-wrapping.
  - Bottom sticky navigation bar (`.col-nav`) provides generous touch targets (min 44px) and clean icon alignment.
  - Profile card stats, cover styles grid, and action buttons reflow responsively.
  - Post action row (reply, like, bookmark, delete) handles compact widths gracefully.
  - SVG benchmark charts render fluently using fluid percentage widths (`max-width: 100%`).
- **Tactile Feedback & Transitions**:
  - Tactile physical press state (`transform: scale(0.97)`) on all primary, outline, and navigational buttons.
  - Spring pop animation (`@keyframes heartPop`) on heart like action.
  - Accessible `:focus-visible` styling for enhanced keyboard and assistive device navigation.

### FR-5.5: University Seed Dataset & 5-Minute Deployment (WBS 5.5)
- **Realistic Dataset (`db/seed.sql`)**:
  - Seed fixtures covering diverse university entities (e.g., FAST Computing Society, IEEE Student Branch, ACM Chapter, departmental faculty, students).
  - Pre-populated realistic posts, hashtags (`#CampusConnect`, `#Hackathon2026`, `#Orientation`), replies, follows, and likes.
- **Zero-Friction Deployment Guide (`docs/DEPLOYMENT.md`)**:
  - Single-page recipe for bootstrapping on a clean XAMPP install within 5 minutes.

---

## 3. Database Schema Increment (Sprint 5)

### 3.1 MySQL Migration (`db/migrate_sprint5_notifications_mysql.sql`)
```sql
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `actor_id` INT NOT NULL,
  `type` VARCHAR(32) NOT NULL,
  `reference_id` INT NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notifications_user_unread` (`user_id`, `is_read`),
  INDEX `idx_notifications_created` (`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notifications_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 4. Verification and Quality Gates

1. **Unit Test Coverage**:
   - `tests/NotificationTest.php` validates notification lifecycle (creation, unread counters, marking read, cascade cleanup).
   - Zero regression across all prior test suites (`UserTest`, `PostTest`, `SocialTest`, `SessionTest`, `Sprint4Test`).
2. **Device Responsiveness Testing**:
   - Verified across desktop (1440px), tablet (768px, 1024px), standard mobile (390px, 414px), and narrow mobile (360px).
3. **Security Audit Check**:
   - Every state-altering action verified with PDO prepared statements and CSRF token matching.
   - All HTML output escaped with `htmlspecialchars()`.
