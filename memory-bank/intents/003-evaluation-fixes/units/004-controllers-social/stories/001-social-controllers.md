---
story: 001-social-controllers
unit: 004-controllers-social
intent: 003-evaluation-fixes
status: ready
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:00:00Z
---

# Story: Post and Social Actions Are Handled by Controllers

As a developer,
I want post, like, follow, reply and bookmark actions handled by controllers,
So that the social features follow the same separation as the rest of the app.

## Acceptance Criteria
- [ ] `PostController` (already exists) extended or complemented by `SocialController` covering like/follow/reply/bookmark actions; pages delegate.
- [ ] AJAX responses unchanged; suite green.

## Estimate
5 story points · Priority: Must

## Verification
- Full suite green; like/follow/reply/bookmark flows smoke-tested live (AJAX responses byte-identical in shape).
