#!/usr/bin/env bash
# restore-pointers.sh — re-apply the ai-workspace session protocol after Laravel Boost
# regenerates AGENTS.md / CLAUDE.md / GEMINI.md.
#
# What it does:
#   1. If any of AGENTS.md / CLAUDE.md / GEMINI.md starts with `<laravel-boost-guidelines>`,
#      back up that fresh Boost output to .ai/laravel-boost.md (so you don't lose updated rules).
#   2. Overwrite AGENTS.md / CLAUDE.md / GEMINI.md / AGENT.md with the canonical pointer content.
#
# Usage:
#   bash scripts/restore-pointers.sh           # apply
#   bash scripts/restore-pointers.sh --check   # report drift, exit 1 if pointers were clobbered

set -euo pipefail

ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
cd "$ROOT"

CHECK_ONLY=0
if [[ "${1:-}" == "--check" ]]; then
  CHECK_ONLY=1
fi

is_boost_dump() {
  local file="$1"
  [[ -f "$file" ]] && head -1 "$file" | grep -q '<laravel-boost-guidelines>'
}

DRIFT=0
for f in AGENTS.md CLAUDE.md GEMINI.md; do
  if is_boost_dump "$f"; then
    DRIFT=1
    echo "DRIFT: $f is a Boost dump, not a pointer."
  fi
done

if [[ "$CHECK_ONLY" -eq 1 ]]; then
  if [[ "$DRIFT" -eq 1 ]]; then
    echo "Run: bash scripts/restore-pointers.sh"
    exit 1
  fi
  echo "OK: pointer files intact."
  exit 0
fi

# Back up fresh Boost dump (any of the three is identical) before overwriting.
for f in AGENTS.md CLAUDE.md GEMINI.md; do
  if is_boost_dump "$f"; then
    echo "Backing up fresh Boost guidelines from $f → .ai/laravel-boost.md"
    cp "$f" .ai/laravel-boost.md
    break
  fi
done

# --- AGENTS.md (canonical workspace protocol) ---
cat > AGENTS.md <<'AGENTS_EOF'
# AGENTS.md — Universal AI Session Protocol (IEEPIS)

This file is the **single source of truth** for every AI coding tool used in this repo. Read it first. Every other rule file (`CLAUDE.md`, `GEMINI.md`, `AGENT.md`, `.cursorrules`, `.cursor/rules/*.mdc`, `.windsurfrules`, `.kilocode/rules.md`) is a pointer here.

Target tools: Claude Code, Cursor, Antigravity / Gemini Code Assist, Kilo AI, OpenCode, Zed, Windsurf, GPT/Codex, Aider, and any future agent.

---

## 1. Session Start Protocol

1. Read `SecondBrain/INDEX.md` + the four always-loaded indexes (skip silently if symlink missing):
   - `SecondBrain/Errors/INDEX.md`
   - `SecondBrain/Knowledge/INDEX.md`
   - `SecondBrain/Decisions/INDEX.md`
   - `SecondBrain/Projects/INDEX.md`
2. Read `.ai/handoff.md` — last session state, blockers, next actions. **Always.**
3. Read `.ai/memory.md` section index (top 20 lines) — load only relevant sections.
4. If the task touches Laravel/Filament/Eloquent code: also read `.ai/laravel-boost.md`.
5. If the user specifies a role: load `.agents/<role>.md`.
6. Confirm context loaded in one line, then wait for the task.

## 2. Session End Protocol

1. Update `.ai/handoff.md` (≤25 lines).
2. Update `.ai/tasks.md`.
3. Major decision → append to `.ai/decisions.md`.
4. Error / surprise → capture in `SecondBrain/Errors/`.
5. Non-obvious observation → `SecondBrain/Learnings/`.

## 3. Token-Saving Rules

- Scoped reads only.
- One task per session.
- Never re-paste project context — reference it.
- `.ai/*` files ≤50 lines (except `laravel-boost.md`); `handoff.md` ≤25.

## 4. Project-Specific Framework Rules

Laravel 11 / FilamentPHP v3 / Livewire v3 / PHP 8.4 / MySQL / Sail conventions live in **`.ai/laravel-boost.md`**. Load on demand only.

## 5. Boost Regeneration Safety

If `php artisan boost:install` is re-run, this file gets overwritten. Run `bash scripts/restore-pointers.sh` to put the pointer back. The script also moves the fresh Boost output into `.ai/laravel-boost.md`.

---

> Stateless reasoning + persistent markdown memory + scoped roles = portable AI teammate.

**This is the truncated restore-script copy. The full canonical version lived here before the last Boost regeneration — restore from git history if you need every section.**
AGENTS_EOF

# --- CLAUDE.md ---
cat > CLAUDE.md <<'CLAUDE_EOF'
# Claude Code Instructions (IEEPIS)

**Canonical rules live in `AGENTS.md`. Read it first.**

## Session start
1. Read `AGENTS.md`.
2. Follow the protocol (handoff.md → memory.md index → optional role).
3. For Laravel/Filament tasks: load `.ai/laravel-boost.md`.

## Claude-Code-specific
- `.claude/rules/*.md` are auto-loaded enforcement mirrors of `AGENTS.md`.
- `.claude/skills/` are Boost-managed framework skills; auto-activate per their triggers.
- Activate roles via: *"Use the `<role>` role."*
- Use Boost MCP tools (`search-docs`, `database-query`, etc.).

If you are any other AI tool reading this, go to `AGENTS.md`.
CLAUDE_EOF

# --- GEMINI.md ---
cat > GEMINI.md <<'GEMINI_EOF'
# Gemini Code Assist / Antigravity Instructions (IEEPIS)

**Canonical rules live in `AGENTS.md`. Read it first.**

1. Read `AGENTS.md`.
2. Follow the protocol (handoff.md → memory.md index → optional role).
3. For Laravel/Filament tasks: load `.ai/laravel-boost.md`.

`.aiexclude` and `.gemini/` config respected. Activate roles via: *"Use the `<role>` role."*

If you are any other AI tool reading this, go to `AGENTS.md`.
GEMINI_EOF

# --- AGENT.md (singular) ---
cat > AGENT.md <<'AGENT_EOF'
# AGENT.md (IEEPIS)

**Canonical rules live in `AGENTS.md` (plural). Read it first.**

This singular file is kept for tools that look for it specifically. All content is consolidated:
- Session protocol → `AGENTS.md`
- Project identity / domain rules → `.ai/memory.md`
- Framework conventions → `.ai/laravel-boost.md`
- Module map → `.ai/context.json`
- Architecture → root `ARCHITECTURE.md` (or `.ai/architecture.md` for the slim summary)
- Decisions → `.ai/decisions.md`
- Current state → `.ai/handoff.md`
- Backlog → `.ai/tasks.md`
AGENT_EOF

echo
echo "Restored: AGENTS.md, CLAUDE.md, GEMINI.md, AGENT.md"
echo "Note: AGENTS.md restored to a *truncated* canonical pointer body."
echo "      If you want the full original canonical version, recover it from git:"
echo "      git checkout <commit-before-boost-regenerated> -- AGENTS.md"
