---
unit: 007-ai-auto-moderation
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Unit Brief: Automated Content Moderation & Safety Screening

## Assigned Owner
**Asad** (22K-5147) · Branch: `feature/unit-007-ai-auto-moderation`

## Purpose
Supplement human reporting with automated keyword, toxicity, and spam pattern analysis to automatically flag policy-violating content into the Admin moderation queue upon creation.

## Scope
### In Scope
- Class `classes/AutoModerator.php` with regex patterns, profanity dictionary, and spam heuristics (e.g. repeated links, scam keywords).
- Hook into `post_action.php` and `reply_action.php` on create/update.
- When score exceeds threshold, insert row into `reports` with `reporter_id: 0` (identified as `System Auto-Mod`).
- Admin UI badge: Tag automated flags with a distinctive "Automated AI Flag" pill in `admin.php#reports`.

### Out of Scope
- Direct unreviewed permanent account deletion (all automated flags require final human admin confirmation).

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-7 | Automated safety screening triggering system reports into moderation queue | Must |
