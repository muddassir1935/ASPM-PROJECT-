---
unit: 002-cache-chain
intent: 003-evaluation-fixes
phase: construction
status: completed
created: 2026-11-18T08:00:00Z
updated: 2026-11-19T12:00:00Z
---

# Unit Brief: Real Chain of Responsibility for Cache Drivers

## Assigned Owner
**Faiz Hussain** (23K-2061) · Branch: `feature/fix-003-oop-foundation`

## Purpose
Fix the overstated-pattern deduction (−0.5 marks): the report claims "Chain of Responsibility" but `Cache.php` just picks a backend with conditionals. Make the claim true with a real handler chain — or deliberately correct the report instead.

## Scope
### In Scope
- A handler chain (phpredis handler → TinyRedisClient handler → memory handler) over `CacheDriverInterface` (from unit 001): each handler tries its backend and passes the request on failure.
- Fallback behaviour unchanged: the app still runs with no Redis.
- Alternative (2 points, recorded as a deliberate decision in the story): remove the "Chain of Responsibility" claim from the report and describe the fallback accurately.

### Out of Scope
- New cache backends. Changes to cache keys, TTLs, or call sites.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-2 | Real Chain of Responsibility for cache drivers (OOP-2) | Should |

## Estimate
3 story points (Should). Depends on unit 001 (`CacheDriverInterface`). Estimates are sized from the code; the owner confirms or re-sizes at the planning session.
