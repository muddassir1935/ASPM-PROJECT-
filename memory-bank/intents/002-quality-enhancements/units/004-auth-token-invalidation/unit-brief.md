---
unit: 004-auth-token-invalidation
intent: 002-quality-enhancements
phase: construction
status: completed
created: 2026-11-18T09:10:00Z
updated: 2026-11-21T20:47:00Z
---

# Unit Brief: Remember-Token Invalidation on Password Change & Reset

## Assigned Owner
**Haseeb Asif** (23K-0539) · Branch: `feature/fix-004-auth-token-invalidation`

## Purpose
Fix critical session security loophole: when a user changes password in `profile_action.php` or an admin resets it in `admin_action.php`, active "Remember Me" persistent tokens (`remember_tokens` table) must be invalidated so compromised tokens cannot re-authenticate.

## Scope
### In Scope
- Add `User::revokeRememberTokens(int $userId): bool` method.
- Call `revokeRememberTokens()` in `profile_action.php` (on `change_password`).
- Call `revokeRememberTokens()` in `admin_action.php` (on `reset_password`).
- Clear the current user's remember cookie if present.

### Out of Scope
- Changing password hash algorithm (Argon2id/Bcrypt remains unchanged).

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-4 | Invalidate remember tokens upon password change and admin password reset | Must |

## Finding (22 Nov 2026 — investigated by Muddassir & Muse, unit closed as not-applicable)

The defect this unit assumed does not exist:

- There is **no `remember_tokens` table** in `db/schema.sql` — the schema has exactly 12 tables (users, posts, follows, replies, bookmarks, sessions, likes, hashtags, post_hashtags, notifications, login_attempts, reports).
- There is **no remember-me cookie feature** anywhere in the app (grep-verified across all PHP files). The only "remember" in the codebase is `Cache::remember()`, a cache helper, unrelated to login.
- The threat the brief cites — a compromised persistent cookie re-authenticating after a password change — cannot occur because no such cookie exists.

What already covers credential change: password change revokes all OTHER sessions (`ProfileController::handleChangePassword` → `DbSessionHandler::destroySessionsForUser`, current session kept); admin password reset revokes ALL sessions for the user (`AdminController::handleResetPassword`). Building a remember-me feature to satisfy the criteria would be a new feature — out of scope for intent 002's fixes-only rule — and would add attack surface the night before the freeze. No code was changed for this unit.
