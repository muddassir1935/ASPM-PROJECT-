---
story: 001-revoke-remember-tokens
unit: 004-auth-token-invalidation
intent: 002-quality-enhancements
status: ready
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
---

# Story: Revoke Persistent Login Tokens on Credential Change

As a security-conscious user,
I want changing my password to sign out all other devices and persistent login cookies,
So that any compromised cookie on a public campus computer can no longer access my account.

## Acceptance Criteria
- [ ] Changing password deletes all rows for that `user_id` from `remember_tokens`.
- [ ] Admin reset deletes all rows for that `user_id` from `remember_tokens`.
- [ ] Subsequent request using old cookie redirects to `login.php`.
