# Architecture (slim summary)

> Full long-form architecture lives in root `ARCHITECTURE.md` and the machine-readable map in `.ai/context.json`. This file is the scoped read for an AI agent — load only when working on structure.

## Layers
- **HTTP** → Laravel 11 routes (`routes/web.php`, `routes/auth.php`); `routes/api.php` intentionally empty (Decision 007)
- **Auth** → Laravel + Spatie Permission roles: `super-admin`, `sdo-admin`, `school-admin`, `technician`, `viewer`
- **UI** → FilamentPHP v3 panel at `/admin`, Livewire-rendered, Tailwind v3
- **Models** → `app/Models/` with `SoftDeletes`, `LogsActivity` traits; school-scoped models use a global scope from `app/Scopes/`
- **Services** → `app/Services/` (business logic, NOT controllers)
- **Observers** → `app/Observers/` (Equipment QR generation, ticket numbering)
- **Jobs / Scheduling** → `bootstrap/app.php` + `routes/console.php` (Laravel 11 style)

## Module Map
See `.ai/memory.md` `Modules` section for the table; full relationship graph in `.ai/context.json`.

## Data Flow (typical)
1. User logs in (Filament panel) → role + school_id loaded into session
2. Filament Resource lists records → global scope auto-filters by school for `school_admin`
3. Mutations → Eloquent → Observer fires (QR, ticket #) → Spatie ActivityLog records mutation
4. Equipment assignment → `EquipmentAssignment` model enforces one-active rule

## External Services
- MySQL 8 (Sail container)
- Redis (queue, cache — Sail container)
- Mailpit (local SMTP — Sail container)
- Google OAuth (Socialite, production only)

## Where the protocol files live
| Concern | File |
|---|---|
| Session protocol (canonical) | `AGENTS.md` |
| Tool pointer files | `CLAUDE.md`, `GEMINI.md`, `AGENT.md`, `.cursor/rules/main.mdc`, `.cursorrules`, `.windsurfrules`, `.kilocode/rules.md` |
| Project memory | `.ai/{memory,handoff,tasks,decisions,coding_rules,architecture}.md` |
| Framework conventions (on-demand) | `.ai/laravel-boost.md` |
| Module map (machine-readable) | `.ai/context.json` |
| Roles | `.agents/{architect,debugger,refactor,pm,reviewer,assistant,scheduler,reminder}.md` |
| Boost MCP skills | `.agents/skills/`, `.claude/skills/` |
| Cross-project brain | `SecondBrain/` (symlink → `~/SecondBrain`) |
