# Claude Code Instructions (IEEPIS)

**Canonical rules live in `AGENTS.md`. Read it first.** This file only adds Claude-Code-specific notes.

## Session start
1. Read `AGENTS.md` — session protocol, file map, token rules.
2. Follow the protocol there (handoff.md → memory.md index → optional role).
3. For Laravel/Filament tasks, also load `.ai/laravel-boost.md` (project-specific framework rules).

## Claude-Code-specific rules
- `.claude/rules/*.md` are **auto-loaded enforcement mirrors** of sections from `AGENTS.md`. `AGENTS.md` is the source; edit it first, then update the mirrors.
- `.claude/skills/` are Boost-managed framework skills (`laravel-best-practices`, `socialite-development`, `tailwindcss-development`) — they auto-activate per their own triggers.
- Use agent roles via: *"Use the `<role>` role."* — roles live in `.agents/`.
- Prefer `Edit` / `Read` / `Write` tools over shell `cat`/`sed`/`echo`.
- Use Boost MCP tools (`search-docs`, `database-query`, `database-schema`, `browser-logs`) instead of manual alternatives.

## If you are any other AI tool reading this
You are in the wrong file. Go to `AGENTS.md`.
