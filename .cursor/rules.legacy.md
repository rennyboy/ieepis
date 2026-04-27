# IEEPIS — AI Instructions for Cursor IDE

## ⚡ Quick Start for AI

Read these files in order before writing any code:
1. `AGENT.md` — Rules, architecture, tech stack, module map
2. `ARCHITECTURE.md` — Request lifecycle, data model, directory structure
3. `DECISIONS.md` — Why key choices were made (do not reverse without reason)

## Project Identity

- **Name**: IEEPIS (ICT Equipment and Employee Profile Information System)
- **Purpose**: DepEd Philippines school-division ICT inventory management
- **Admin Panel**: FilamentPHP v3 at `/admin`
- **Stack**: PHP 8.4 / Laravel 11 / Filament v3 / Livewire v3 / MySQL 8 / Tailwind v3

## Critical Rules (Never Break These)

```
1. All commands → vendor/bin/sail prefix
2. No employee_id FK on equipment table → use equipment_assignments
3. Core models use soft deletes → no hard deletes
4. One active assignment per equipment → enforced in model
5. school_admin role → global Eloquent scope (cannot see other schools)
6. Run pint after PHP changes → vendor/bin/sail bin pint --dirty --format agent
7. PHPUnit class-based tests only → not Pest
8. Search docs via MCP search-docs tool before Filament code
```

## Gold Standard File

When unsure how to structure a Filament Resource, copy the pattern from:
`app/Filament/Resources/EquipmentResource.php`

## MCP Tools Available

- `search-docs` — search Laravel/Filament docs (always use before coding)
- `database-query` — run read-only DB queries
- `database-schema` — inspect table structure
- `get-absolute-url` — resolve app URLs
- `browser-logs` — read browser errors
