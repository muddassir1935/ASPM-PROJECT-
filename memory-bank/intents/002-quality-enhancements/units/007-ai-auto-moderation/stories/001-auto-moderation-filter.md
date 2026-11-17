---
story: 001-auto-moderation-filter
unit: 007-ai-auto-moderation
intent: 002-quality-enhancements
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Story: Automated Heuristic Content Screening

As a Campus Administrator,
I want toxic and spam posts to be automatically flagged immediately when posted,
So that harassment or malicious scam links are queued for review before widespread harm occurs.

## Acceptance Criteria
- [ ] Submitting a post containing prohibited abuse or spam keywords triggers `AutoModerator::screen()`.
- [ ] An automated report entry is created in `reports` with `status: 'pending'` and `reason: 'spam'` or `'harassment'`.
- [ ] Admin reports queue prominently displays the report with a "System Auto-Mod" badge and detected trigger word snippet.
- [ ] Clean student posts are processed with < 5ms latency overhead.
