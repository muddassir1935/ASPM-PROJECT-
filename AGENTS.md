# CampusConnect — Agent Instructions (AGENTS.md)

CampusConnect is a university microblogging & announcement hub (ASPM course project,
Group Code AIR: Muddassir Rizwan, Faiz Hussain, Qaim Mehdi). PHP 8 OOP + MySQL/PDO,
no framework, no build step. Posts are 280-character "chirps".

## AI-DLC workflow (specs.md)

This repo runs the AI-Driven Development Lifecycle via **specs.md**
(MIT, `fabriqaai/specs.md`, installed version 0.1.74, AI-DLC flow, Google Antigravity
tooling — see `.specsmd/manifest.yaml`).

- **Agents** (`.specsmd/aidlc/agents/`): Master (orchestrates + routes), Inception
  (requirements, units, stories, bolt plans), Construction (executes bolts),
  Operations (build, deploy, verify, monitor).
- **In Antigravity, run the slash commands** from `.agent/workflows/`:
  `/specsmd-master-agent`, `/specsmd-inception-agent`, `/specsmd-construction-agent`,
  `/specsmd-operations-agent`.
- **Skills**: `.specsmd/aidlc/skills/{master,inception,construction,operations}/`
- **Templates**: `.specsmd/aidlc/templates/` — bolt types: DDD (5 stages:
  Model → Design → ADR → Implement → Test), Simple (3 stages: Spec → Implement → Test),
  Spike.
- **Memory bank** (`memory-bank/`): the project's persistent context. Agents are
  stateless — they reload context from the memory bank every session. Read the
  standards before writing code; write artifacts back after each step.
- Start here: `.specsmd/aidlc/quick-start.md`. Workflow rules:
  `.agents/rules/aidlc-workflow.md`.
- **Do not edit files under `.specsmd/`** — they are the installed framework and an
  upstream reinstall overwrites them. Project content goes in `memory-bank/`.

## Human-in-the-loop — non-negotiable

AI proposes, the human validates. Never advance past a checkpoint without the user:
after requirements, after unit decomposition, after story creation, after each bolt
stage, before deployment. Never push, merge, tag, or deploy without the user's
explicit approval. Never invent meeting notes, contribution figures, or test counts —
only git and chat history that can be verified.

## Project state (brownfield)

The project is DONE and submitted (16 Nov 2026, frozen build `424390e`: 22-page
report, 12-slide deck, release zip). The repo is in the **Operations** phase. New
work follows the AI-DLC flow from a fresh intent — do not rewrite history into bolts.
The five sprints are recorded as completed units in
`memory-bank/intents/001-campusconnect/units.md`.

## Repo rules (enforced)

- No direct commits to `main`. One feature branch per feature:
  `feature/sprint-<n>-<name>`; iterate in the same branch, never a new branch per
  iteration.
- OOP, classes in `classes/`. PDO prepared statements only. `password_hash` /
  `password_verify` for passwords. `htmlspecialchars` on output. CSRF token on every
  state-changing form/AJAX call. Permission checks via `Auth::can()` /
  `Authorization::can()` — never compare roles directly.
- Roles: `student`, `society_admin`, `system_admin` (14 permissions in
  `classes/Authorization.php`). Never change a role except through the admin panel —
  never a raw DB UPDATE as the app path.
- Every new behaviour gets tests. Full suite must stay green:
  `php tests/run_all.php` (412 tests: 398 on throwaway SQLite + 14 MySQL, skipped
  when MySQL is down).
- Sync before work on this shared repo: fetch, read new commits/authors, branches,
  open PRs — never duplicate or overwrite a teammate's work.
- `.htaccess` / `router.php` protect `db/`, `tests/`, `config/`, `classes/`, `docs/`.
  Never weaken them. Never commit secrets, datasets, or weights.
- Do not touch `docs/CONTRIBUTION.md:107` and `docs/sprint-1-2-review-retro.md:261`.
- Do not run `scripts/package_release.py` except for the final packaging step.

## Layout

- Pages at repo root (`index.php`, `profile.php`, `explore.php`, ...); `*_action.php`
  are AJAX endpoints. `classes/` domain classes, `config/` DB config, `db/` schema +
  seed + migrations, `tests/` suites, `docs/` SRS increments, manuals, audits.
- Setup: import `db/schema.sql` then `db/seed.sql` (demo password `StrongPass123!`),
  serve via XAMPP/Apache or `php -S localhost:8000 router.php`. Full guide:
  `docs/DEPLOYMENT.md`.
