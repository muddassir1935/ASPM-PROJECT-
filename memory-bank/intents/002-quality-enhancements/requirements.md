---
intent: 002-quality-enhancements
phase: inception
status: draft
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Requirements: Quality Enhancements & Team Expansion

## Intent Overview
Following the AI-DLC (AI-Driven Development Life Cycle) framework, this intent defines 9 modular enhancements and quality improvements for **CampusConnect**, partitioned into discrete units assigned to each of the 9 team members.

## Business Goals

| Goal | Success Metric | Priority |
|------|----------------|----------|
| Expand rich student media & interaction | Posts support image attachments and polls | Must |
| Provide private communication | Direct 1-on-1 peer messaging operational | Must |
| Proactive safety & moderation | Automated keyword/pattern screening auto-flags to Admin queue | Should |
| Search discovery & accessibility | Filter facets on Explore & WCAG 2.1 AA keyboard/theme compliance | Should |
| Real-time alerts & test hardening | Notification polling active & automated test suite coverage >= 95% | Must |

---

## Functional Requirements (FR)

### FR-1: AI-DLC Governance & Architecture Management
- **Owner**: Muddassir Rizwan (23K-0784)
- **Description**: Maintain and govern the AI-DLC lifecycle, memory-bank consistency, status verification, and ADR alignment across all branches.
- **Acceptance Criteria**: All 9 unit briefs, stories, and bolts follow memory-bank schema. Status integrity checks pass with 0 inconsistencies.
- **Priority**: Must
- **Assigned Unit**: `001-aidlc-governance`

### FR-2: Post Media & Image Attachments
- **Owner**: Faiz Hussain (23K-2061)
- **Description**: Enable students and society admins to attach images (PNG, JPG, WebP up to 5MB) to their posts with responsive lightbox view.
- **Acceptance Criteria**: Secure MIME-validation, storage in `uploads/posts/`, rendering in feed cards, and image removal on post soft-delete.
- **Priority**: Must
- **Assigned Unit**: `002-media-attachments`

### FR-3: Peer-to-Peer Direct Messaging (DMs)
- **Owner**: Qaim Mehdi (23K-2075)
- **Description**: Enable authenticated campus peers (`@nu.edu.pk`) to initiate and maintain 1-on-1 private direct message threads.
- **Acceptance Criteria**: Database table `messages`, conversation list in `messages.php`, profile "Send Message" action, and read/unread status.
- **Priority**: Must
- **Assigned Unit**: `003-direct-messaging`

### FR-4: Interactive Campus Polls & Voting
- **Owner**: Haseeb Asif (23K-0539)
- **Description**: Allow users to attach a 2-to-4 option voting poll to posts with customizable expiration duration and real-time percentage tallies.
- **Acceptance Criteria**: One vote per user enforcement, interactive selection buttons, and bar-chart style visual breakdown.
- **Priority**: Should
- **Assigned Unit**: `004-campus-polls`

### FR-5: Faceted Search & Category Discovery
- **Owner**: Muhammad Hadi (23K-0755)
- **Description**: Upgrade `explore.php` with filter chips/tabs to refine search results by category: All, Announcements, Students, Societies, and Date Range.
- **Acceptance Criteria**: URL query parameter preservation, SQL query optimization with `ESCAPE '!'`, and empty state handling.
- **Priority**: Should
- **Assigned Unit**: `005-faceted-search`

### FR-6: WCAG 2.1 AA Accessibility & DB Theme Persistence
- **Owner**: Yuneeb (23K-0733)
- **Description**: Persist light/dark theme preference to user profile in MySQL/SQLite and enforce keyboard traps, ARIA attributes, and focus states on all modals.
- **Acceptance Criteria**: Theme persists across different browsers/devices on login; Report and Delete modals dismissable via `Esc` with trapped tab navigation.
- **Priority**: Should
- **Assigned Unit**: `006-accessibility-theme`

### FR-7: Automated Content Moderation (Rule/AI Screening)
- **Owner**: Asad (22K-5147)
- **Description**: Screen newly submitted posts and replies against predefined campus safety heuristics and toxic language regex to auto-flag violations.
- **Acceptance Criteria**: Violating content automatically generates a report in `reports` with `reporter_id: 0 (System Auto-Mod)`, visible in Admin review queue.
- **Priority**: Must
- **Assigned Unit**: `007-ai-auto-moderation`

### FR-8: Real-Time Notification Polling & Toast Alerts
- **Owner**: Saad (22K-5039)
- **Description**: Provide background AJAX heartbeat to fetch unread notification counts every 30 seconds and display non-intrusive UI toast popups.
- **Acceptance Criteria**: Lightweight JSON endpoint `api/unread_counts.php`, dynamic badge counter in header without full page reload.
- **Priority**: Should
- **Assigned Unit**: `008-realtime-notifications`

### FR-9: Performance Benchmarks & Extended Test Automation
- **Owner**: Shahryar (22K-4435)
- **Description**: Expand PHPUnit/custom test suites for all new units (Media, DMs, Polls, Auto-Mod) and benchmark Redis caching vs MySQL direct queries.
- **Acceptance Criteria**: 100% pass on all new unit tests, updated `benchmark.php` with throughput charts, and updated `docs/DEPLOYMENT.md`.
- **Priority**: Must
- **Assigned Unit**: `009-testing-benchmarks`

---

## Non-Functional Requirements (NFR)

| Category | Requirement | Target |
|----------|-------------|--------|
| Performance | Page response time on local server | < 100ms with Redis cache hit |
| Security | File uploads & XSS immunity | Strict whitelist validation, CSRF tokens on all POST endpoints |
| Compatibility | Dual-database support | Zero MySQL-only syntax breaking SQLite unit tests |
| Accessibility | Contrast & keyboard navigation | WCAG 2.1 Level AA compliance |
