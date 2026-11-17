# CampusConnect — Team Task Allocation & AI-DLC Execution Guide

**Intent:** `002-quality-enhancements` (AI-DLC Phase: Inception Complete / Construction Ready)  
**Date:** 17 Nov 2026  
**Status:** Approved for Team Implementation  

---

## Team Roster & Module Assignments

| # | Member Name | Roll Number | Assigned Unit / Feature | Git Branch |
|---|-------------|-------------|-------------------------|------------|
| 1 | **Muddassir Rizwan** | 23K-0784 | `001-aidlc-governance` (Architecture & AI-DLC Lead) | `feature/unit-001-aidlc-governance` |
| 2 | **Faiz Hussain** | 23K-2061 | `002-media-attachments` (Post Image Uploads) | `feature/unit-002-media-attachments` |
| 3 | **Qaim Mehdi** | 23K-2075 | `003-direct-messaging` (1-on-1 Direct Messaging) | `feature/unit-003-direct-messaging` |
| 4 | **Haseeb Asif** | 23K-0539 | `004-campus-polls` (Interactive Campus Polls & Voting) | `feature/unit-004-campus-polls` |
| 5 | **Muhammad Hadi** | 23K-0755 | `005-faceted-search` (Search Filters & Facets) | `feature/unit-005-faceted-search` |
| 6 | **Yuneeb** | 23K-0733 | `006-accessibility-theme` (A11y WCAG 2.1 AA & DB Theme) | `feature/unit-006-accessibility-theme` |
| 7 | **Asad** | 22K-5147 | `007-ai-auto-moderation` (Heuristic Content Screening) | `feature/unit-007-ai-auto-moderation` |
| 8 | **Saad** | 22K-5039 | `008-realtime-notifications` (AJAX Heartbeat & Alerts) | `feature/unit-008-realtime-notifications` |
| 9 | **Shahryar** | 22K-4435 | `009-testing-benchmarks` (Regression Tests & Benchmarking) | `feature/unit-009-testing-benchmarks` |

---

## Detailed Task Breakdown per Member

### 1. Muddassir Rizwan (23K-0784) — AI-DLC Governance & Architecture
- **Goal:** Manage the AI-DLC lifecycle, verify status cascading in `memory-bank/`, review PRs against coding standards, and ensure 0 regression across all branches.
- **Artifacts:**
  - `memory-bank/intents/002-quality-enhancements/`
  - `.github/pull_request_template.md`
  - `memory-bank/standards/decision-index.md`

### 2. Faiz Hussain (23K-2061) — Post Image & Media Attachments
- **Goal:** Enable users to attach images (PNG, JPG, WebP up to 5MB) to posts with secure MIME verification and responsive card display.
- **Files to Create/Modify:**
  - `classes/Media.php`
  - `post_action.php` (Handle `$_FILES['media']`)
  - `db/migrate_7_media.sql` (Creates `post_media` table)
  - `index.php` & `post.php` (Render image thumbnail in feed card)

### 3. Qaim Mehdi (23K-2075) — Peer-to-Peer Direct Messaging (DMs)
- **Goal:** Provide 1-on-1 private messaging between verified students/faculty.
- **Files to Create/Modify:**
  - `classes/DirectMessage.php`
  - `messages.php` (Conversation list + message view)
  - `message_action.php` (Send message endpoint with CSRF)
  - `db/migrate_8_messages.sql` (Creates `messages` table)
  - `profile.php` (Add "Send Message" button)

### 4. Haseeb Asif (23K-0539) — Interactive Campus Polls & Student Voting
- **Goal:** Allow post authors to attach 2-to-4 option polls with real-time percentage progress bars.
- **Files to Create/Modify:**
  - `classes/Poll.php`
  - `poll_action.php` (Submit vote endpoint with CSRF and duplicate prevention)
  - `db/migrate_9_polls.sql` (Creates `polls`, `poll_options`, `poll_votes` tables)
  - Compose box UI & Post card rendering

### 5. Muhammad Hadi (23K-0755) — Faceted Search & Category Discovery
- **Goal:** Upgrade `explore.php` with filter chips/tabs (All, Announcements, People, Societies, Hashtags).
- **Files to Create/Modify:**
  - `explore.php` (Category filter pill navigation)
  - `classes/Post.php` (Extend `searchPosts` to support `is_announcement` and date range filters)
  - `classes/User.php` (Filter by `role` facet)

### 6. Yuneeb (23K-0733) — WCAG 2.1 AA Accessibility & Database Theme Persistence
- **Goal:** Persist theme preference to DB and ensure accessible keyboard navigation across all modal dialogs.
- **Files to Create/Modify:**
  - `db/migrate_10_theme.sql` (`ALTER TABLE users ADD COLUMN theme_preference ...`)
  - `profile_action.php` (Save theme preference on toggle)
  - `assets/js/main.js` (Trap focus in Report & Delete modals, handle `Esc` dismissal, restore focus)

### 7. Asad (22K-5147) — Automated Content Moderation (Rule/AI Screening)
- **Goal:** Automatically screen submitted posts/replies against toxicity heuristics and auto-queue violations in the Admin Moderation Queue.
- **Files to Create/Modify:**
  - `classes/AutoModerator.php` (Regex and heuristic scanner)
  - `post_action.php` & `reply_action.php` (Trigger scan before or upon persist)
  - `admin.php#reports` (Display automated badge: "System Auto-Mod")

### 8. Saad (22K-5039) — Real-Time Notification Heartbeat & Toast Alerts
- **Goal:** Periodically poll unread counts and display non-intrusive floating toast notifications when tab is open.
- **Files to Create/Modify:**
  - `api/unread_counts.php` (Lightweight JSON endpoint: unread notifications + unread messages)
  - `assets/js/main.js` (30s polling heartbeat, badge updates, floating toast DOM element)

### 9. Shahryar (22K-4435) — Extended Regression Tests & Redis Benchmarking
- **Goal:** Build automated test suites for all new units, stress test Redis caching, and ensure 100% test pass rate.
- **Files to Create/Modify:**
  - `tests/MediaTest.php`, `tests/DirectMessageTest.php`, `tests/PollTest.php`, `tests/AutoModeratorTest.php`
  - `benchmark.php` (Redis vs Database latency comparison benchmarks)
  - `docs/DEPLOYMENT.md` (Update setup guide for all new tables)

---

## AI-DLC Team Workflow (How Each Member Should Work)

1. **Pull Latest Main:**
   ```bash
   git checkout main
   git pull origin main
   ```
2. **Create Feature Branch:**
   ```bash
   git checkout -b feature/unit-00X-your-feature
   ```
3. **Inspect Your Unit Brief:**
   Read `memory-bank/intents/002-quality-enhancements/units/00X-your-feature/unit-brief.md` to see your scope, entities, and acceptance criteria.
4. **Follow the Construction Phase (`/specsmd-construction-agent`):**
   - Write unit tests first or alongside the feature.
   - Run `php tests/run_all.php` to verify no existing tests break.
5. **Open Pull Request:**
   Submit PR to `main` with summary of criteria satisfied and test output.
