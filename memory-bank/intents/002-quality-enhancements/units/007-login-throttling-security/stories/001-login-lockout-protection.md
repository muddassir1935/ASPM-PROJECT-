---
story: 001-login-lockout-protection
unit: 007-login-throttling-security
intent: 002-quality-enhancements
status: ready
created: 2026-11-18T09:10:00Z
updated: 2026-11-18T09:10:00Z
---

# Story: Brute-Force Login Lockout & Throttle Protection

As a system administrator,
I want failed login attempts to be rate-limited,
So that malicious scripts cannot brute-force student passwords on the campus network.

## Acceptance Criteria
- [ ] 5 consecutive failed logins on an email locks the account for 300 seconds (5 min).
- [ ] During lockout, login attempts return a descriptive 429-friendly error without DB query.
- [ ] Valid password on attempt 4 clears the failed counter immediately.
