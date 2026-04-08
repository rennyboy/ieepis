# DECISIONS.md — Architectural Decision Log

> **For AI Agents**: Before proposing a refactor or alternative approach, check if the decision you're questioning is documented here. These are intentional choices, not accidents.

---

## Decision 001 — FilamentPHP for Admin Panel (Not Custom Blade/Inertia)

**Date**: Initial build  
**Status**: Active

### Context
IEEPIS needs a full CRUD admin panel for ICT officers who are not developers. The UI must handle complex forms, relationships, table filtering, and role-based access.

### Decision
Use **FilamentPHP v3** as the entire admin layer.

### Rationale
- FilamentPHP provides complete CRUD scaffolding with Livewire — no custom controller/view wiring needed.
- Built-in role-based access via `canViewAny()`, `canCreate()`, `canEdit()` hooks.
- Integrated table features: search, filter, sort, pagination, bulk actions.
- DepEd ICT officers are comfortable with structured admin panels.
- Saving ~3 months of custom frontend development.

### Consequences
- All UI is server-rendered (no SPA). Acceptable for internal tools.
- Livewire handles reactivity — no Vue/React needed.
- Styling is Tailwind CSS via Filament's bundled build — custom CSS is limited.

---

## Decision 002 — Equipment Accountability via Assignment Table (Not FK on Equipment)

**Date**: Initial build  
**Status**: Active — **Do Not Change Without Discussion**

### Context
DepEd equipment tracking requires a full history of who was accountable for a device, when, and with which document (PAR/ICS/RRSP). A simple `employee_id` FK on `equipment` can only hold the current assignment.

### Decision
Accountability is tracked exclusively in the `equipment_assignments` table. The `equipment` table has **no employee FK**.

### Rationale
- Government audit requirements demand a complete, immutable transfer history.
- PAR and ICS documents must reference who held accountability on a specific date.
- One-to-many: one equipment → many assignments (one active at a time).

### Consequences
- Querying "who holds this device?" requires a join through `equipment_assignments` with `is_active = true`.
- The `EquipmentAssignment` model enforces the one-active-at-a-time rule.
- `EquipmentResource` uses a Relation Manager for assignments — not a simple select.

---

## Decision 003 — Soft Deletes on All Core Models

**Date**: Initial build  
**Status**: Active

### Context
DepEd ICT officers occasionally delete records by mistake. Government data retention policies also require records to be preserved.

### Decision
All core models (`School`, `Employee`, `Equipment`, `Document`, `Ticket`) use Laravel soft deletes (`SoftDeletes` trait).

### Rationale
- Mistakes are recoverable without database-level intervention.
- Regulatory compliance — government asset records must not be permanently deleted.
- Spatie ActivityLog still tracks soft-deleted records.

### Consequences
- All queries automatically exclude soft-deleted records (via scope).
- Use `->withTrashed()` or `->onlyTrashed()` when you explicitly need deleted records.
- Filament tables need `->withSoftDeletes()` on the table query if restoration is needed.

---

## Decision 004 — Spatie ActivityLog for Audit Trail

**Date**: Initial build  
**Status**: Active

### Context
Government assets require an audit trail of all mutations — who changed what and when.

### Decision
Use **Spatie Laravel ActivityLog** on `School`, `Employee`, and `Equipment` models via the `LogsActivity` trait.

### Rationale
- Drop-in solution with zero custom code for basic audit logging.
- Stores old/new values automatically.
- Queryable via the `activity_log` table.

### Consequences
- Do **not** manually create activity log entries for mutations handled by the trait.
- Do **not** remove the `LogsActivity` trait from core models.

---

## Decision 005 — School-Scoped Global Eloquent Scope

**Date**: Initial build  
**Status**: Active

### Context
`school_admin` users must never access data from other schools. Implementing this per-query is error-prone and a security risk.

### Decision
A **global Eloquent scope** (in `app/Scopes/`) automatically restricts all queries to the authenticated user's school when the user has the `school_admin` role.

### Rationale
- Security by default — a developer cannot accidentally expose another school's data.
- Applies at the database level, not the presentation layer.
- Consistent behavior across all Resources, Widgets, and API endpoints.

### Consequences
- `school_admin` can never see another school's data — ever — without explicitly bypassing the scope.
- `super_admin` and `sdo_admin` roles are unaffected (scope checks role first).
- When writing tests for `school_admin`, ensure the test user has a `school_id` assigned.

---

## Decision 006 — Laravel Sail for Development (Docker)

**Date**: Hybrid setup added  
**Status**: Active

### Context
The team uses different operating systems (Linux, macOS). Environment consistency is required.

### Decision
Use **Laravel Sail** (Docker) as the canonical development environment.

### Rationale
- Consistent PHP 8.4, MySQL 8, Redis, Mailpit versions across all machines.
- `vendor/bin/sail` prefix ensures all commands run inside the container.
- Hybrid mode available: local PHP + Dockerized services (see `docker-compose.hybrid.yml`).

### Consequences
- **All commands must be prefixed with `vendor/bin/sail`**. Never run `php artisan` or `composer` directly.
- The app runs at `http://localhost` (port 80 via Sail).
- Database runs at `127.0.0.1:3306` inside the container network.

---

## Decision 007 — No Dedicated REST API (Filament-Only UI)

**Date**: Initial build  
**Status**: Active — revisit if mobile app is required

### Context
IEEPIS is used by ICT officers via web browser. No mobile app or third-party system integration was required at build time.

### Decision
No dedicated REST/GraphQL API. All data access is via Filament's server-rendered Livewire components.

### Rationale
- Reduces attack surface — no public API endpoints to secure.
- Reduces complexity — no API versioning, token management, or serialization layer needed.
- Filament + Livewire provides sufficient interactivity for the use case.

### Consequences
- If a mobile app or external integration is added later, an API layer will need to be built.
- The `routes/api.php` file exists but is largely empty — this is intentional.

---

## Decision 008 — PHPUnit for Testing (Not Pest)

**Date**: Project setup  
**Status**: Active

### Decision
Use **PHPUnit** with class-based tests. Do not use Pest.

### Rationale
- Project convention established at start.
- PHPUnit is more explicit and easier to onboard new team members unfamiliar with Pest.

### Consequences
- All new tests must be class-based PHPUnit tests.
- If you see a Pest-style test, convert it to PHPUnit.
- Create tests with: `vendor/bin/sail artisan make:test --phpunit {Name}Test`

---

## Decision 009 — QR Codes Auto-Generated on Equipment Creation

**Date**: Initial build  
**Status**: Active

### Context
Physical equipment needs a scannable QR code tag for quick identification during inventory audits.

### Decision
QR codes are auto-generated by an **Eloquent Observer** when an `Equipment` record is created. The code encodes: `property_no`, `serial_no`, `brand/model`.

### Rationale
- Zero manual steps for ICT officers — QR tags are ready immediately.
- Observer pattern keeps this logic decoupled from the Resource form.

### Consequences
- Do not attempt to generate QR codes manually in the Resource — the Observer handles it.
- QR code images are stored in `storage/app/public/`.
