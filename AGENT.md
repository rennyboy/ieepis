# AGENT.md — AI Agent Instruction Manual for IEEPIS

> **This is the primary context file for any AI agent working in this repository.**
> Read this first. Then read `ARCHITECTURE.md`. Then read `DECISIONS.md`.
> Do NOT skip any of these three files before writing code.

---

## 🎯 Project Purpose

**IEEPIS** (ICT Equipment and Employee Profile Information System) is a **school-division-level ICT inventory management system** for the Department of Education (DepEd), Philippines.

It tracks:
- ICT equipment inventory (computers, printers, peripherals, etc.)
- Equipment accountability and assignment history (PAR, ICS, RRSP documents)
- Employee/personnel directory with position and separation tracking
- Internet connectivity per school
- Support tickets for equipment issues
- Documents (PAR, ICS, IAR, DR, OR, SI, WMR, RRSP, RRPE)

**Users**: SDO ICT Officers, School Admins, Technicians, Viewers within a DepEd School Division.

---

## 🏗️ Tech Stack (Exact Versions — Do Not Upgrade Without Approval)

| Package | Version |
|---------|---------|
| PHP | 8.4 |
| Laravel | 11 |
| FilamentPHP | v3 |
| Livewire | v3 |
| Tailwind CSS | v3 (via Filament) |
| MySQL | 8.0+ |
| Laravel Sail | v1 (Docker dev environment) |
| Spatie Permission | Installed |
| Spatie ActivityLog | Installed |
| PHPUnit | 11 |

**All commands must be prefixed with `vendor/bin/sail`** (e.g. `vendor/bin/sail artisan migrate`).

---

## 🚦 Rules — Follow These Without Exception

### PHP / Laravel
1. Use **PHP 8.4 features**: constructor promotion, enums, named arguments, readonly properties.
2. Always use **curly braces** for control structures, even single-line.
3. All methods must have **explicit return types** and **typed parameters**.
4. Use **PHPDoc blocks** over inline comments.
5. Use **TitleCase** for Enum keys: `GoodCondition`, `ForDisposal`.
6. Never put business logic in controllers. Use **service classes** or **model methods**.
7. Use `vendor/bin/sail artisan make:` for ALL new PHP files.

### Filament
1. Use `static::make()` pattern for component initialization.
2. Resources live in `app/Filament/Resources/` — one file per model.
3. Resource pages (List/Create/Edit) auto-generate inside `app/Filament/Resources/{Model}Resource/Pages/`.
4. Forms use `Forms\Components` namespace; Tables use `Tables\Columns` namespace.
5. Use `relationship()` on Select/Checkbox when linking to related models.
6. Always search docs with the `search-docs` MCP tool before writing Filament code.

### Database / Eloquent
1. **Never add `employee_id` to equipment** — accountability is tracked through `equipment_assignments` only.
2. When modifying a column in a migration, include ALL previous column attributes or they will be dropped.
3. All core models use **soft deletes** — use `->withTrashed()` when querying deleted records.
4. Use the `casts()` **method** on models, not the `$casts` property.

### Authorization
1. Check the user's **Spatie Permission role** before assuming what they can see.
2. `school_admin` role has a **global scope** that restricts all queries to their school only.
3. Always implement `canViewAny()` / `canCreate()` / `canEdit()` on Resources.

### Testing
1. Write **PHPUnit feature tests** — not Pest.
2. Use model **factories** — never manually insert records in tests.
3. Authenticate as a Filament user before testing admin panel routes.
4. Run tests with: `vendor/bin/sail artisan test --compact --filter=TestName`.

### Code Style
1. Run Pint after every PHP change: `vendor/bin/sail bin pint --dirty --format agent`.
2. Use descriptive method names: `isAccountableOfficer()` not `accountable()`.

---

## 📁 Module Map — Where Things Live

| Domain | Model | Resource | Notes |
|--------|-------|----------|-------|
| Schools | `School` | `SchoolResource` | Top-level organizational unit |
| Personnel | `Employee` | `EmployeeResource` | Teaching + non-teaching staff |
| Equipment | `Equipment` | `EquipmentResource` | IEEPIS core; DCP/non-DCP |
| Assignments | `EquipmentAssignment` | `AssignmentResource` | Accountability trail |
| Documents | `Document` | `DocumentResource` | PAR, ICS, IAR, etc. |
| Tickets | `Ticket` | `TicketResource` | Support tickets |
| Connectivity | `InternetConnection` | `InternetConnectionResource` | ISP details per school |
| Districts | `District` | `DistrictResource` | School grouping |
| Users | `User` | `UserResource` | Admin panel access |
| Access Control | `ApprovedUser` | `ApprovedUserResource` | Registration whitelist |

---

## 🔄 How to Add a New Feature

1. **Check existing patterns** — look at `EquipmentResource.php` as the gold standard.
2. **Create the migration**: `vendor/bin/sail artisan make:migration`
3. **Create/update the model**: `vendor/bin/sail artisan make:model`
4. **Create the factory**: `vendor/bin/sail artisan make:factory`
5. **Create the Filament resource**: `vendor/bin/sail artisan make:filament-resource {Name} --generate`
6. **Add authorization** in the resource's static methods.
7. **Write feature tests**: `vendor/bin/sail artisan make:test --phpunit {Name}Test`
8. **Run Pint**: `vendor/bin/sail bin pint --dirty --format agent`
9. **Run the tests**: `vendor/bin/sail artisan test --compact`

---

## ⚠️ Known Constraints & Gotchas

- **No global state** — school-scoped queries use Eloquent global scopes, not session hacks.
- **No external stateful services** — the app is stateless HTTP (except DB + file storage).
- **Ticket numbers** auto-generate as `TKT-YYYY-XXXX` per year — do not override this logic.
- **QR codes** auto-generate on equipment creation — the `Equipment` observer handles this.
- **One active assignment** rule — enforced in the `EquipmentAssignment` model. Never bypass this with mass-insert.
- **Activity log** — mutations to `School`, `Employee`, `Equipment` automatically log via Spatie. Don't double-log.
- **Vite manifest error** → run `vendor/bin/sail npm run build` or `vendor/bin/sail npm run dev`.

---

## 🧠 AI Tool Configuration

This project is configured for multiple AI tools:

| Tool | Config File | Context File |
|------|-------------|--------------|
| Antigravity | `GEMINI.md` | This file + `ARCHITECTURE.md` |
| Claude / Claude Code | `CLAUDE.md` | This file + `ARCHITECTURE.md` |
| Cursor | `.cursor/mcp.json` | This file + `ARCHITECTURE.md` |
| OpenCode | `opencode.json` | This file + `ARCHITECTURE.md` |
| VS Code AI | `.vscode/settings.json` | This file + `ARCHITECTURE.md` |
| Any Agent | `AGENT.md` (this file) | Universal entry point |

---

## 📚 Documentation Index

| File | Purpose |
|------|---------|
| `README.md` | Human-facing project overview and setup guide |
| `AGENT.md` | **This file** — AI agent instructions |
| `ARCHITECTURE.md` | System design, data model, request lifecycle |
| `DECISIONS.md` | Why key technical decisions were made |
| `TASKS.md` | Current backlog and roadmap |
| `project.json` | Machine-readable project metadata |
| `prompts/` | Reusable AI prompt templates |
| `.ai/context.json` | AI indexer — module relationships and API surface |
