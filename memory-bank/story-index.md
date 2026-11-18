# Story Index — CampusConnect

Single-file mode (`memory-bank.yaml`: `story-index.mode: single-file`).

## Historical stories

The delivered product predates AI-DLC: its stories were the sprint backlog
items, and they live in the sprint documentation, not as per-unit story
files:

- `docs/sprint-2-srs-increment.md`, `docs/sprint-3-srs-increment.md`,
  `docs/sprint-4-srs-increment.md`, `docs/sprint-5-srs-increment.md`
- `docs/sprint-1-2-review-retro.md` (review + retro notes for all five sprints)
- `docs/wbs-mapping.md` (WBS coverage table)
- `memory-bank/intents/001-campusconnect/units.md` (unit index)

No retroactive story files were created — inventing story artifacts for
finished work would be fiction, and this repo does not invent history.

## New stories (Intent 002: Bug Fixes & Hardening)

| Story | Intent / Unit | Owner | Status |
|---|---|---|---|
| `001-aidlc-orchestration.md` | `002-quality-enhancements` / `001-aidlc-governance` | Muddassir Rizwan (23K-0784) | ready |
| `001-bookmark-count-sync.md` | `002-quality-enhancements` / `002-bookmark-counter-fix` | Faiz Hussain (23K-2061) | completed |
| `001-moderation-notifications.md` | `002-quality-enhancements` / `003-moderation-feedback-pagination` | Qaim Mehdi (23K-2075) | ready |
| `001-revoke-remember-tokens.md` | `002-quality-enhancements` / `004-auth-token-invalidation` | Haseeb Asif (23K-0539) | ready |
| `001-clean-explore-pagination.md` | `002-quality-enhancements` / `005-explore-search-casing-fix` | Muhammad Hadi (23K-0755) | ready |
| `001-profile-tab-pager.md` | `002-quality-enhancements` / `006-profile-tab-pagination` | Yuneeb (23K-0733) | ready |
| `001-login-lockout-protection.md` | `002-quality-enhancements` / `007-login-throttling-security` | Asad (22K-5147) | ready |
| `001-badge-sync-expiry-feedback.md` | `002-quality-enhancements` / `008-badge-sync-session-ux` | Saad (22K-5039) | ready |
| `001-regression-test-coverage.md` | `002-quality-enhancements` / `009-regression-test-suite` | Shahryar (22K-4435) | ready |

## New stories (Intent 003: Evaluation Fixes — OOP + DB)

Sir's evaluation (OOP 6/10, DB 8.5/10), re-evaluation Tue 24 Nov 2026. Estimates
in story points, sized from the code on 18 Nov 2026 (see the planning pack);
each owner confirms or re-sizes at the planning session.

| Story | Intent / Unit | Owner | Status |
|---|---|---|---|
| `001-model-base-interfaces.md` | `003-evaluation-fixes` / `001-oop-foundation` | Unassigned | ready |
| `001-cache-handler-chain.md` | `003-evaluation-fixes` / `002-cache-chain` | Unassigned | ready |
| `001-auth-profile-controllers.md` | `003-evaluation-fixes` / `003-controllers-auth` | Unassigned | ready |
| `001-social-controllers.md` | `003-evaluation-fixes` / `004-controllers-social` | Unassigned | ready |
| `001-admin-controllers.md` | `003-evaluation-fixes` / `005-controllers-admin` | Unassigned | ready |
| `001-injectable-helpers.md` | `003-evaluation-fixes` / `006-helpers-objects` | Unassigned | ready |
| `001-phpunit-migration.md` | `003-evaluation-fixes` / `007-phpunit-migration` | Unassigned | ready |
| `001-report-column-constraints.md` | `003-evaluation-fixes` / `008-db-constraints` | Unassigned | ready |
| `001-notification-fk.md` | `003-evaluation-fixes` / `009-db-fk-reference` | Unassigned | ready |
| `001-sqlite-schema-generator.md` | `003-evaluation-fixes` / `010-schema-generator` | Unassigned | ready |
