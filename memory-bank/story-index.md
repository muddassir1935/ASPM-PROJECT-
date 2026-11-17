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

## New stories

New work starts with a new intent (`/specsmd-inception-agent intent-create`),
units under that intent, and stories at
`memory-bank/intents/{NNN}-{intent}/units/{UUU}-{unit}/stories/{SSS}-{slug}.md`.
List them here as they are created:

| Story | Intent / Unit | Owner | Status |
|---|---|---|---|
| `001-aidlc-orchestration.md` | `002-quality-enhancements` / `001-aidlc-governance` | Muddassir Rizwan (23K-0784) | ready |
| `001-post-image-upload.md` | `002-quality-enhancements` / `002-media-attachments` | Faiz Hussain (23K-2061) | ready |
| `001-peer-direct-messages.md` | `002-quality-enhancements` / `003-direct-messaging` | Qaim Mehdi (23K-2075) | ready |
| `001-campus-polls.md` | `002-quality-enhancements` / `004-campus-polls` | Haseeb Asif (23K-0539) | ready |
| `001-search-filter-facets.md` | `002-quality-enhancements` / `005-faceted-search` | Muhammad Hadi (23K-0755) | ready |
| `001-theme-persistence-a11y.md` | `002-quality-enhancements` / `006-accessibility-theme` | Yuneeb (23K-0733) | ready |
| `001-auto-moderation-filter.md` | `002-quality-enhancements` / `007-ai-auto-moderation` | Asad (22K-5147) | ready |
| `001-unread-count-polling.md` | `002-quality-enhancements` / `008-realtime-notifications` | Saad (22K-5039) | ready |
| `001-extended-test-benchmarks.md` | `002-quality-enhancements` / `009-testing-benchmarks` | Shahryar (22K-4435) | ready |
