---
story: 001-auth-profile-controllers
unit: 003-controllers-auth
intent: 003-evaluation-fixes
status: completed
created: 2026-11-18T08:00:00Z
updated: 2026-11-21T19:45:00Z
---

# Story: Auth and Profile Actions Are Handled by Controllers

As a developer,
I want login, registration, profile and password actions handled by controller classes,
So that request handling is separated from page scripts.

## Acceptance Criteria
- [x] `AuthController` and `ProfileController` own the auth/profile `*_action.php` flows; the pages become thin entry points (include + delegate).
- [x] CSRF, validation and redirects keep working exactly as before; suite green.

## Estimate
5 story points · Priority: Must

## Verification
- Full suite green; login/register/profile/password flows smoke-tested live (valid + invalid inputs).
