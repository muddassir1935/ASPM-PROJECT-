---
unit: 004-auth-token-invalidation
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
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
