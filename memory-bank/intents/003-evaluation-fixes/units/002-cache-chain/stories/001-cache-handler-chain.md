---
story: 001-cache-handler-chain
unit: 002-cache-chain
intent: 003-evaluation-fixes
status: ready
created: 2026-11-18T08:00:00Z
updated: 2026-11-18T08:00:00Z
---

# Story: Cache Backend Selection Is a Real Chain of Responsibility

As a developer,
I want cache-backend selection to be a real Chain of Responsibility,
So that the report's pattern claim is true instead of an if/else on a string.

## Acceptance Criteria
- [ ] A handler chain (phpredis → TinyRedisClient → memory) where each handler tries its backend and passes the request on failure — no string-switched if/else choosing the backend.
- [ ] Fallback behaviour unchanged: app still runs with no Redis (verified by existing cache tests).
- [ ] OR, as a recorded deliberate decision: the "Chain of Responsibility" claim is removed from the report and the fallback is described accurately.

## Estimate
3 story points · Priority: Should · Depends on: unit 001 (`CacheDriverInterface`)

## Verification
- Full suite green; cache tests pass with Redis up and with Redis down (memory fallback path).
- Report text matches the implementation (checked in review).
