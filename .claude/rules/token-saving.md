# Token-Saving Rules

> **Enforcement mirror of `AGENTS.md` §4.** `AGENTS.md` is the source of truth. Edit it first, then sync here. Run `bash scripts/check-drift.sh` to verify.

- **Scoped reads only.** Say *"Read `.ai/memory.md` Stack section"* — not *"Read `.ai/memory.md`"*.
- **One task per session.** Start a new chat for unrelated topics.
- **Never re-explain project context in chat.** It lives in `.ai/memory.md`. Reference it, don't paste it.
- **Prefer one well-scoped request** over five clarification rounds.
- **Cheap tools for cheap tasks.** Claude Code / GPT-5 / Opus for design, debugging, architecture. Cursor tab / Kilo / local models for syntax fixes, boilerplate, tiny edits.
- **File size caps:** `.ai/*` under 50 lines each. `handoff.md` under 25. If a file grows, split it.
- **Indexers:** `.cursorignore`, `.kilocodeignore`, `.aiexclude`, `.codeiumignore` exclude `.obsidian/` + build output. `SecondBrain/` stays indexed so prompts and decisions are searchable.
