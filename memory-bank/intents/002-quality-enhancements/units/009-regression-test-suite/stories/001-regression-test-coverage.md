---
story: 001-regression-test-coverage
unit: 009-regression-test-suite
intent: 002-quality-enhancements
status: completed
created: 2026-11-18T09:10:00Z
updated: 2026-11-21T20:47:00Z
---

# Story: Automated Regression Coverage for Bug Fixes

As a quality assurance engineer,
I want automated tests asserting each of the 8 bug fixes,
So that future changes do not re-introduce bookmark count mismatches or remember-token vulnerabilities.

## Acceptance Criteria
- [x] Automated tests cover all 8 bug fixes across existing features. (7 real fixes covered; the 8th — remember-token revocation — is N/A, no such feature exists; see unit 004 finding.)
- [x] All test suites pass 100% on SQLite and MySQL test environments. (601/601 SQLite locally; MySQL suite green in CI.)
- [x] Overall test suite count increases from 412 to 430+. (601 at close-out.)

See the unit brief for the check-by-check verification mapping.

## Estimate
5 story points
