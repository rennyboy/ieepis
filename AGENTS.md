# AGENTS.md — Universal AI Session Protocol (IEEPIS)

This file is the **single source of truth** for every AI coding tool used in this repo. Read it first. Every other rule file (`CLAUDE.md`, `GEMINI.md`, `AGENT.md`, `.cursorrules`, `.cursor/rules/*.mdc`, `.windsurfrules`, `.kilocode/rules.md`) is a pointer here.

Target tools: Claude Code, Cursor, Antigravity / Gemini Code Assist, Kilo AI, OpenCode, Zed, Windsurf, GPT/Codex, Aider, and any future agent.

---

## 1. Session Start Protocol (do this before any task)

1. Read `SecondBrain/INDEX.md` + the four always-loaded indexes (skip silently if symlink missing — it's optional cross-project memory):
   - `SecondBrain/Errors/INDEX.md` (failure library — scan for symptoms relevant to today's task)
   - `SecondBrain/Knowledge/INDEX.md` (distilled rules)
   - `SecondBrain/Decisions/INDEX.md` (active major decisions)
   - `SecondBrain/Projects/INDEX.md` (confirm IEEPIS is registered)
2. Read `.ai/handoff.md` — last session state, blockers, next actions. **Always.**
3. Read `.ai/memory.md` section index (top 20 lines) — load only the sections relevant to the task.
4. If the task touches Laravel/Filament/Eloquent code: also read the relevant section of `.ai/laravel-boost.md` (project-specific framework rules).
5. If the user specifies a role: load `.agents/<role>.md`.
6. Confirm context loaded in one line: `"Loaded: brain[errors,knowledge,decisions,projects] + handoff + memory[sections] + <role>."` Then wait for the task.

**Do not** read `.ai/architecture.md`, `.ai/decisions.md`, `.ai/coding_rules.md`, `.ai/laravel-boost.md`, or any individual SecondBrain entry file unless the task matches. Token budget is finite.

---

## 2. Session End Protocol (do this after meaningful work)

1. Update `.ai/handoff.md` — what was done, blockers, next actions. Keep under 25 lines.
2. Update `.ai/tasks.md` — check off completed tasks, add new ones discovered.
3. If a major decision was made → append an entry to `.ai/decisions.md` with Decision / Reason / Trade-off. If it generalizes beyond this project → also capture in `SecondBrain/Decisions/`.
4. **If an error or surprise was hit:** capture using `SecondBrain/Prompts/error-capture.md`. Either increment an existing `Errors/INDEX.md` row's `Hits` or create a new entry from `Templates/error.md`.
5. **If a non-obvious observation was made:** drop it into `SecondBrain/Learnings/` (staging area for promotion to `Knowledge/`).
6. Never summarize work only in chat. It will be lost. Write it to `handoff.md` or the appropriate SecondBrain location.

### Rotation (when size caps are hit — do not silently violate them)
- `handoff.md` exceeds 25 lines → move "What Was Completed" to `SecondBrain/Daily Notes/YYYY-MM-DD.md` before writing new entries.
- `.ai/memory.md` section exceeds 50 lines → split that section into `.ai/memory-<topic>.md` and update the index.
- `.ai/tasks.md` exceeds 50 lines → move completed items to `SecondBrain/Daily Notes/YYYY-MM-DD.md`.
- `.ai/decisions.md` exceeds 50 lines → split by year into `SecondBrain/Decisions/YYYY.md`.

---

## 3. File Map

### Repo memory (per project)
| File | Purpose | Load when |
|---|---|---|
| `.ai/handoff.md` | Last session state → next actions | **Always, first** |
| `.ai/memory.md` | Project identity, stack, constraints | Every session, scoped to section |
| `.ai/tasks.md` | Sprint tasks + backlog | Task planning, end of session |
| `.ai/architecture.md` | Modules, services, data flow (slim — defers to root `ARCHITECTURE.md`) | Only when working on structure |
| `.ai/decisions.md` | Dated decision log | Only when revisiting a choice |
| `.ai/coding_rules.md` | Memory file + AI interaction rules | Only when uncertain of convention |
| `.ai/laravel-boost.md` | Laravel/Filament/Eloquent project conventions (Boost-generated) | Only when writing framework code |
| `.ai/context.json` | Machine-readable module/relationship map | When mapping models or relationships |

### Agent roles (per project)
| Role | File | Use for |
|---|---|---|
| Architect | `.agents/architect.md` | System design, structure, data flow |
| Debugger | `.agents/debugger.md` | Root cause, minimal fixes |
| Refactor | `.agents/refactor.md` | Clean code, readability |
| PM | `.agents/pm.md` | Task prioritization, milestones |
| Reviewer | `.agents/reviewer.md` | Code quality, risk |
| Assistant | `.agents/assistant.md` | Daily general-purpose helper |
| Scheduler | `.agents/scheduler.md` | Time-blocking, daily plan |
| Reminder | `.agents/reminder.md` | Reminders, accountability |

`.agents/skills/` (Boost-managed Filament/Laravel/Tailwind skills) auto-activates per its own triggers — do not edit by hand.

Activate a role by telling the AI: *"Use the `<role>` role."*

### Strategic vault (cross-project — `SecondBrain/`)

Lives at `~/SecondBrain` (canonical), symlinked into this project at `./SecondBrain`. Optional but recommended.

Always-loaded surfaces (~500 lines combined cap):
- `SecondBrain/INDEX.md` — entry point
- `SecondBrain/Errors/INDEX.md` — failure library (cap 200 lines)
- `SecondBrain/Knowledge/INDEX.md` — distilled rules (cap 100 lines)
- `SecondBrain/Decisions/INDEX.md` — major decisions (cap 100 lines)
- `SecondBrain/Projects/INDEX.md` — multi-project registry (cap 50 lines)

On-demand: `Bugs/`, `Learnings/`, `Daily Notes/`, `Templates/`, `Prompts/`, `Assistant/`.

---

## 4. Token-Saving Rules (enforced, not optional)

- **Scoped reads only.** Say *"Read `.ai/memory.md` Stack section"* — not *"Read `.ai/memory.md`"*.
- **One task per session.** Start a new chat for unrelated topics.
- **Never re-explain project context in chat.** It lives in `.ai/memory.md`. Reference it, don't paste it.
- **Cheap tools for cheap tasks.** Claude Code / GPT-5 / Opus for design and debugging. Cursor tab / Kilo / local models for syntax fixes and boilerplate.
- **File size caps:** `.ai/*` files stay under 50 lines each except `laravel-boost.md` (allowed to be larger because it's loaded only on demand). `handoff.md` under 25.
- **Indexers:** `.cursorignore`, `.kilocodeignore`, `.aiexclude`, `.codeiumignore` exclude build output (`node_modules`, `vendor`, `public/build`, `storage`). `SecondBrain/` stays **indexed**.

---

## 5. Bootstrap for tools without auto-load

Tools like ChatGPT or generic agents won't auto-load anything. Paste this as your first message:

> Read `.ai/handoff.md` and the index at the top of `.ai/memory.md`. If the task requires it, also read the matching section of `.ai/memory.md` or `.ai/laravel-boost.md`. Follow the session protocol in `AGENTS.md`. Confirm loaded, then await task.

---

## 6. Cross-Tool Cheat Sheet

| Tool | Entrypoint | Behavior |
|---|---|---|
| Claude Code | `CLAUDE.md` + `.claude/rules/*.md` → AGENTS.md | Auto-loads `CLAUDE.md` and `.claude/rules/`. Mirrors of sections of this file. |
| Cursor | `.cursor/rules/main.mdc` (native) + `.cursorrules` (legacy) → AGENTS.md | |
| Windsurf | `.windsurfrules` → AGENTS.md | |
| Kilo AI | `.kilocode/rules.md` → AGENTS.md | |
| Antigravity / Gemini | `GEMINI.md` → AGENTS.md | |
| OpenCode | `opencode.json` + `AGENTS.md` (native) | |
| Zed | `.zed/` + `AGENTS.md` (native) | |
| Aider | `.aider.conf.yml` references AGENTS.md | |
| Codex / GPT | Paste the bootstrap above | Manual. |

---

## 7. Project-Specific Framework Rules

Laravel 11 / FilamentPHP v3 / Livewire v3 / PHP 8.4 / MySQL / Sail conventions live in **`.ai/laravel-boost.md`** (Boost-generated). Load that file on demand when writing framework code — not on every session.

Project domain rules (DepEd-specific accountability model, equipment_assignments invariants, school-scoped queries, etc.) live in **`.ai/memory.md`** under the relevant section.

Root-level docs (`README.md`, root `ARCHITECTURE.md`, root `DECISIONS.md`, `TASKS.md`, `MANIFEST.md`, `*_DEPLOYMENT*.md`) are human-facing. AI agents should prefer `.ai/*` for current state.

---

## 8. Safety: Boost Regeneration

If `php artisan boost:install` is re-run, it will overwrite `AGENTS.md`, `CLAUDE.md`, and `GEMINI.md` with Boost defaults. To restore the workspace protocol after regeneration:

```bash
bash scripts/restore-pointers.sh
```

That script puts the pointer files back and re-relocates fresh Boost output to `.ai/laravel-boost.md`.

---

## 9. Principle

> Stateless high-value reasoning engine + persistent markdown memory + scoped role prompts = portable AI teammate.

Claude / GPT / Cursor are **consultants**, not memory. Memory lives here on disk.
