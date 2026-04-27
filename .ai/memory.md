# Project Memory — IEEPIS

## Section Index (load only what you need)
- `Identity` — name, owners, scope
- `Purpose` — what this exists for
- `Stack` — exact framework/package versions
- `Domain Rules` — DepEd-specific invariants
- `Modules` — split into [`memory-modules.md`](memory-modules.md); load on demand
- `Priorities` — current focus
- `Constraints` — hard limits and gotchas

---

## Identity
- **Name**: IEEPIS — ICT Equipment and Employee Profile Information System
- **Owner**: rennyboyjr@gmail.com
- **Scope**: school-division-level inventory system for DepEd Philippines
- **Users**: SDO ICT Officers, School Admins, Technicians, Viewers (per-school scope via Spatie roles)

## Purpose
Track ICT equipment inventory, accountability/assignment history (PAR, ICS, RRSP), employee directory, school internet connectivity, and support tickets — scoped to a single DepEd School Division Office.

## Stack
- PHP 8.4, Laravel 11, FilamentPHP v3, Livewire v3, Tailwind v3
- Database: PostgreSQL 14+ or MySQL 8.0+ — keep DB-agnostic (Schema builder, no driver-specific SQL)
- Laravel Sail (Docker dev) — prefix every command with `vendor/bin/sail`
- Spatie Permission, Spatie ActivityLog, PHPUnit 11 (NOT Pest)
- Laravel Boost MCP: `search-docs`, `database-query`, `database-schema`, `browser-logs`

For full Laravel/Filament conventions see `.ai/laravel-boost.md`.

## Domain Rules
- **Identity**: `Employee` is canonical for personal info. `User` is auth-only; reads of `$user->name` / `$user->school_id` delegate via `employees.user_id` relation. One user ↔ at most one employee (unique FK).
- **Equipment has no `employee_id` FK** — accountability tracked through `equipment_assignments` only.
- **One active assignment per equipment** — enforced by `App\Services\AssignmentService` (DB transaction + `lockForUpdate` on equipment row). All Filament create/transfer/return paths must go through it.
- **`school_admin` role has a global scope** restricting all queries to their school.
- **Soft deletes** on all core models — use `->withTrashed()` when needed.
- **QR codes auto-generate** on equipment creation via Observer.
- **Ticket numbers** auto-generate as `TKT-YYYY-XXXX` per year.
- **Activity log** auto-fires for `School`, `Employee`, `Equipment` mutations.
- **Transaction types** (assignments): `Beginning Inventory | Issuance | Transfer | Return`.

## Priorities
1. Stabilize Filament admin (sidebar, exports, role-scoped views)
2. Equipment assignment workflow polish
3. Reporting / DCP dashboard

## Constraints
- No framework upgrades without approval. App is stateless HTTP only. No business logic in controllers — use service classes. All commands via Sail.
