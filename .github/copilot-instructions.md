# GitHub Copilot Instructions for IEEPIS

This is the **IEEPIS** project — ICT Equipment and Employee Profile Information System for DepEd Philippines.

## Tech Stack
- PHP 8.4 / Laravel 11 / FilamentPHP v3 / Livewire v3
- MySQL 8.0 / Tailwind CSS v3 / Laravel Sail (Docker)
- Spatie Permission + ActivityLog / PHPUnit 11

## Mandatory Rules

1. **All terminal commands** must use `vendor/bin/sail` prefix.
2. **Never add `employee_id`** to the `equipment` table. Equipment accountability goes through `equipment_assignments` only.
3. **All core models use soft deletes** — never call `->forceDelete()` on School, Employee, Equipment, Document, or Ticket.
4. **One active assignment per equipment** — enforced in EquipmentAssignment model. Do not bypass.
5. **`school_admin` role** is automatically restricted to their own school via global Eloquent scope.
6. **Run Pint** after every PHP file change: `vendor/bin/sail bin pint --dirty --format agent`.
7. **PHPUnit only** — never write Pest-style tests. Use `vendor/bin/sail artisan make:test --phpunit {Name}`.
8. **Filament Resources** — check `app/Filament/Resources/EquipmentResource.php` as the reference pattern.
9. PHP **explicit return types** and **typed parameters** on every method.
10. **Constructor property promotion** for all new classes.

## Key Files to Read First
- `AGENT.md` — complete AI agent instructions
- `ARCHITECTURE.md` — system design and data model
- `DECISIONS.md` — why things were built a certain way

## Module Reference
| Module | Model | Resource |
|--------|-------|----------|
| Schools | `App\Models\School` | `SchoolResource` |
| Employees | `App\Models\Employee` | `EmployeeResource` |
| Equipment | `App\Models\Equipment` | `EquipmentResource` |
| Assignments | `App\Models\EquipmentAssignment` | `AssignmentResource` |
| Documents | `App\Models\Document` | `DocumentResource` |
| Tickets | `App\Models\Ticket` | `TicketResource` |
| Connectivity | `App\Models\InternetConnection` | `InternetConnectionResource` |
