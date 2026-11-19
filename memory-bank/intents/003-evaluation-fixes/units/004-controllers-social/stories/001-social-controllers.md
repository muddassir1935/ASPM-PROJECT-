---
story: 001-social-controllers
unit: 004-controllers-social
intent: 003-evaluation-fixes
status: completed
created: 2026-11-18T08:00:00Z
updated: 2026-11-19T15:30:00Z
---

# Story: Post and Social Actions Are Handled by Controllers

As a developer,
I want post, like, follow, reply and bookmark actions handled by controllers,
So that the social features follow the same separation as the rest of the app.

## Acceptance Criteria
- [x] `PostController` (already exists) extended or complemented by `SocialController` covering like/follow/reply/bookmark actions; pages delegate.
- [x] AJAX responses unchanged; suite green.

## Estimate
5 story points · Priority: Must

## Verification
- Full suite green; like/follow/reply/bookmark flows smoke-tested live (AJAX responses byte-identical in shape).

## Completion Notes
- `post_action.php` already delegated to `PostController` — left unchanged.
- New `classes/SocialController.php` handles like / follow / bookmark (AJAX) and reply create / delete (PRG); `like_action.php`, `follow_action.php`, `bookmark_action.php`, `reply_action.php` are now thin entry points.
- `tests/SocialControllerTest.php` (38 tests) added to `tests/run_all.php`; full suite green.
- Live smoke: 19 requests against `php -S` (old `main` vs this branch) gave identical status codes, JSON bodies and redirects.
