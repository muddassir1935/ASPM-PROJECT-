---
unit: 007-login-throttling-security
intent: 002-quality-enhancements
phase: construction
status: completed
created: 2026-11-18T09:10:00Z
updated: 2026-11-19T12:35:00Z
---

# Unit Brief: Brute-Force Login Throttling & Progressive Backoff

## Assigned Owner
**Asad** (22K-5147) · Branch: `feature/fix-007-login-throttling`

## Purpose
Harden `login.php` against automated credential guessing and brute-force password cracking on campus accounts.

## Scope
### In Scope
- Track consecutive failed login attempts per email / client IP via `Cache::getInstance()` (fallback to memory/file).
- After 5 consecutive failed attempts, enforce a 5-minute lockout with error: "Too many failed login attempts. Please try again in 5 minutes."
- On successful login, clear the failed attempts counter.
- Add microsecond progressive delay (`usleep`) between consecutive failed attempts.

### Out of Scope
- Adding third-party CAPTCHAs or 2FA SMS services.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-7 | Enforce progressive delay and 5-attempt login lockout to prevent brute-force | Must |
