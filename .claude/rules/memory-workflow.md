# Memory Workflow Rules

> **Enforcement mirror of `AGENTS.md` §1 and §2.** `AGENTS.md` is the source of truth. Edit it first, then sync here. Run `bash scripts/check-drift.sh` to verify.

## Session start (from AGENTS.md §1)
1. Read `.ai/handoff.md` — last session state, blockers, next actions. **Always first.**
2. Read `.ai/memory.md` section index (top 15 lines) — load only the sections relevant to the task.
3. If the user specifies a role: load `.agents/<role>.md`.
4. Confirm: `"Loaded: handoff + memory[sections] + <role>."` Then wait for the task.

Do **not** read `architecture.md`, `decisions.md`, or `coding_rules.md` unless the task requires them.

## Session end (from AGENTS.md §2)
1. Update `.ai/handoff.md` — what was done, blockers, next actions. Keep under 25 lines.
2. Update `.ai/tasks.md` — check off completed, add new.
3. If a major decision was made → append to `.ai/decisions.md` (Decision / Reason / Trade-off).
4. Never summarize work only in chat — write it to `handoff.md`.

## Rotation (when caps are hit)
- `handoff.md` > 25 lines → move "What Was Completed" to `SecondBrain/Daily Notes/YYYY-MM-DD.md` before writing new entries.
- `.ai/memory.md` section > 50 lines → split into `.ai/memory-<topic>.md` and update the index.
