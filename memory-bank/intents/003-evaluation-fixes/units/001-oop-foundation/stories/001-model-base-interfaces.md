---
story: 001-model-base-interfaces
unit: 001-oop-foundation
intent: 003-evaluation-fixes
status: ready
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:00:00Z
---

# Story: Model Classes Share a Base Class and Custom Interfaces

As a developer,
I want the model classes to share a common base class and the design to define its own interfaces,
So that the codebase demonstrates inheritance and polymorphism instead of 18 unrelated classes.

## Acceptance Criteria
- [ ] A `Model` base class holds the PDO connection and shared helpers; the ~10 model classes extend it.
- [ ] At least one custom interface exists and is used — `CacheDriverInterface` (get/set/delete) implemented by the Redis and in-memory drivers.
- [ ] No behaviour change: full suite green (412+ tests) before and after.
- [ ] Report §3 updated to describe the hierarchy honestly (no overstated pattern names).

## Estimate
8 story points · Priority: Must

## Verification
- Full suite: `php tests/run_all.php` green on the branch.
- `grep "extends Model" classes/*.php` lists the migrated models; `grep "implements CacheDriverInterface"` lists the drivers.
