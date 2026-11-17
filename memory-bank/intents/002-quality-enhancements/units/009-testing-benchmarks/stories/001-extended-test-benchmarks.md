---
story: 001-extended-test-benchmarks
unit: 009-testing-benchmarks
intent: 002-quality-enhancements
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Story: Extended Regression Testing & Redis Performance Benchmark

As a QA and DevOps engineer,
I want automated test suites verifying every new unit and benchmark tests comparing Redis throughput against MySQL,
So that no regressions are introduced and our high-performance caching claim is mathematically substantiated.

## Acceptance Criteria
- [ ] New test suites exist for Media, DMs, Polls, and Auto-Mod with 100% pass rate.
- [ ] `php tests/run_all.php` runs all suites with zero failures.
- [ ] `benchmark.php` records latency differences between Redis and Database fallback over 1,000 iterations.
- [ ] `docs/DEPLOYMENT.md` updated with setup verification steps for the new tables.
