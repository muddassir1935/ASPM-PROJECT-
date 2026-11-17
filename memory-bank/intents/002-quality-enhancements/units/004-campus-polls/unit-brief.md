---
unit: 004-campus-polls
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Unit Brief: Interactive Campus Polls & Student Voting

## Assigned Owner
**Haseeb Asif** (23K-0539) · Branch: `feature/unit-004-campus-polls`

## Purpose
Allow students and society leads to create structured multiple-choice polls attached to posts to survey student opinions on workshops, hackathons, and dates.

## Scope
### In Scope
- DB Tables: `polls` (`id`, `post_id`, `question`, `expires_at`), `poll_options` (`id`, `poll_id`, `option_text`), `poll_votes` (`id`, `poll_id`, `option_id`, `user_id`, `created_at`).
- Model `classes/Poll.php` (createPoll, castVote, getPollForPost, hasUserVoted).
- Handler `poll_action.php` with CSRF protection and duplicate vote prevention.
- UI: Poll creation inputs on compose box and dynamic percentage progress bars on feed cards.

### Out of Scope
- Ranked-choice voting (single choice per poll).

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-4 | 2-4 option voting polls attached to posts with live percentage tally | Should |
