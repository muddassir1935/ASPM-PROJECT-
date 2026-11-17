---
trigger: always_on
---

# AI-DLC workflow (specs.md)

CampusConnect runs the AI-Driven Development Lifecycle via specs.md
(`.specsmd/`, AI-DLC flow, version 0.1.74). This rule is the workflow reminder;
the full method lives in `.specsmd/aidlc/quick-start.md` and the agent/skill files.

## The three phases (sequential)

1. **Inception** — Inception Agent: capture an intent, elaborate requirements,
   define system context, decompose into units and stories, plan bolts.
2. **Construction** — Construction Agent: execute bolts. Bolt types:
   `ddd-construction-bolt` (Model → Design → ADR → Implement → Test),
   `simple-construction-bolt` (Spec → Implement → Test), `spike-bolt`.
3. **Operations** — Operations Agent: build, deploy, verify, monitor.

The **Master Agent** orchestrates and routes. In Antigravity, invoke the agents with
the slash commands in `.agent/workflows/` (`/specsmd-master-agent`, etc.).

## Checkpoints — stop and wait for the human

- After requirements elaboration, after unit decomposition, after story creation,
  after each bolt stage, before deployment.
- A checkpoint is a hard gate: do not auto-advance, do not batch past it, do not
  "assume approval". The user validates, then work continues.

## Memory bank — where project state lives

- `memory-bank/project.yaml` — project type (`full-stack-web`) + init metadata.
- `memory-bank/standards/` — tech stack, coding standards, architecture, API
  conventions, UX guide, decision index. Construction MUST load tech-stack and
  coding-standards before generating code (see `.specsmd/aidlc/context-config.yaml`).
- `memory-bank/intents/{NNN}-{name}/` — requirements.md, system-context.md,
  units.md, units/{UUU}-{name}/ (unit-brief.md, stories/).
- `memory-bank/bolts/{BBB}-{unit}/` — bolt.md + stage artifacts. Bolt status:
  `planned` → `in-progress` → `completed` (or `blocked`). Use the
  `.specsmd/aidlc/scripts/bolt-complete.cjs` script for completion — never hand-edit
  bolt/story/unit status across files.
- `memory-bank/operations/` — deployment context.
- `memory-bank/story-index.md` — single-file story index.
- All timestamps in memory-bank artifacts: ISO 8601 **with time and timezone**
  (`YYYY-MM-DDTHH:MM:SSZ`). Schema: `.specsmd/aidlc/memory-bank.yaml`.

## Rules for this repo

- `.specsmd/` is the installed framework — never edit it. Project content goes in
  `memory-bank/` only.
- Brownfield: the product is already built and submitted. The five sprints are
  recorded as completed units (`memory-bank/intents/001-campusconnect/units.md`).
  Do not create retroactive bolts or stories for finished work. New work starts
  with a new intent.
- Every bolt that touches code ends with the test suite green
  (`php tests/run_all.php`) before the stage is marked complete.
- No push, merge, tag, or deploy without the user's explicit approval — the
  Operations phase does not override the repo's approval rule.
