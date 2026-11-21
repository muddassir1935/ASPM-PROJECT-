---
story: 001-revoke-remember-tokens
unit: 004-auth-token-invalidation
intent: 002-quality-enhancements
status: completed
created: 2026-11-18T09:10:00Z
updated: 2026-11-21T20:47:00Z
---

# Story: Revoke Persistent Login Tokens on Credential Change

As a security-conscious user,
I want changing my password to sign out all other devices and persistent login cookies,
So that any compromised cookie on a public campus computer can no longer access my account.

## Acceptance Criteria
- [ ] Changing password deletes all rows for that `user_id` from `remember_tokens`. — NOT APPLICABLE (see Finding: no such table exists)
- [ ] Admin reset deletes all rows for that `user_id` from `remember_tokens`. — NOT APPLICABLE (see Finding)
- [ ] Subsequent request using old cookie redirects to `login.php`. — NOT APPLICABLE (no persistent login cookie exists; session revocation already covers credential change)

## Finding (22 Nov 2026 — investigated by Muddassir & Muse, story closed as not-applicable)

No `remember_tokens` table exists in `db/schema.sql` (12 tables, none of them remember_tokens) and no remember-me cookie code exists anywhere in the app — the defect this story assumed is not real. Password change already revokes all other sessions and admin reset revokes all sessions for the user, so the credential-change threat is already handled. See the unit brief for the full finding. No code was changed.

## Estimate
3 story points
