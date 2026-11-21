---
story: 001-injectable-helpers
unit: 006-helpers-objects
intent: 003-evaluation-fixes
status: completed
created: 2026-11-18T08:00:00Z
updated: 2026-11-21T20:30:00Z
---

# Story: Helpers Are Objects, Not Static Utility Bags

As a developer,
I want Auth, Csrf, Authorization and SafeRedirect to be objects passed in through constructors,
So that helpers are testable and the design uses objects, not static utility bags.

## Acceptance Criteria
- [x] The four classes (7 + 5 + 6 + 3 static methods today) become instantiable with instance methods; call sites receive them via constructors.
- [x] Behaviour unchanged; suite green.

## Estimate
5 story points · Priority: Should

## Verification
- Full suite green; `grep "public static function" classes/Auth.php classes/Csrf.php classes/Authorization.php classes/SafeRedirect.php` returns nothing (except any deliberately kept factory).
