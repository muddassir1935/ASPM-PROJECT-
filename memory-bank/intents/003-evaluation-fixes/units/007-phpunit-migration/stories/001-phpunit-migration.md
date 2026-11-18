---
story: 001-phpunit-migration
unit: 007-phpunit-migration
intent: 003-evaluation-fixes
status: ready
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:00:00Z
---

# Story: The Test Suite Runs on PHPUnit

As a developer,
I want the 412-test suite to run on PHPUnit,
So that the project uses the standard tool instead of a home-made harness.

## Acceptance Criteria
- [ ] All suites run under PHPUnit with the same assertions and the same pass count; `run_all.php` retired or kept as a thin wrapper.

## Estimate
13 story points · Priority: Could — only if all Musts and Shoulds are done with time to spare. If dropped, record it as an explicit decision in the retro.

## Verification
- `vendor/bin/phpunit` (or equivalent) runs the full suite green with the same test count as today's harness.
