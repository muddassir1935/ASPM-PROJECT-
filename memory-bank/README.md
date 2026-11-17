# Memory Bank — CampusConnect

File-based persistent context for the specs.md AI-DLC flow. Agents are stateless:
they reload context from here every session. Keep it current — write artifacts back
after each step, and never let it drift from the code.

## Layout (schema: `.specsmd/aidlc/memory-bank.yaml`)

- `project.yaml` — project type (`full-stack-web`) + init metadata.
- `standards/` — tech stack, coding standards, architecture, API conventions,
  UX guide, decision index. Construction loads tech-stack + coding-standards
  before generating any code.
- `intents/` — high-level goals. `001-campusconnect/` is the delivered product
  (requirements, system context, units). New work starts with a NEW intent.
- `bolts/` — bolt execution records (created by bolt-plan / bolt-start).
- `operations/` — deployment context.
- `story-index.md` — single-file story index.

## Brownfield note

This project was built and submitted (16 Nov 2026, frozen build `424390e`) BEFORE
AI-DLC was installed. The memory bank was seeded from the real repo state on
2026-11-17 (main `a3e1232`): the five sprints are recorded as completed units, not
retroactive bolts or stories. Do not fabricate history — every claim here traces
to git history or `docs/`. The live project docs (SRS increments, manuals,
audits) remain in `docs/`; the memory bank summarizes and points at them.

## Rules

- All timestamps: ISO 8601 with time and timezone (`YYYY-MM-DDTHH:MM:SSZ`).
- Naming: intents `{NNN}-{name}`, units `{UUU}-{name}`, stories
  `{SSS}-{title-slug}.md`, bolts `{BBB}-{unit}/`.
- Bolt completion goes through `.specsmd/aidlc/scripts/bolt-complete.cjs` —
  never hand-edit status across bolt/story/unit files.
