# IEEPIS — Architecture Overview

> **For AI Agents**: Read this file alongside `AGENT.md` before writing any code. This explains the structural decisions so you don't break existing patterns.

---

## 🏗️ System Architecture

IEEPIS is a **monolithic Laravel 11 application** served through a FilamentPHP v3 admin panel. There is no separate API layer — all UI is server-rendered via Livewire + FilamentPHP.

```
┌──────────────────────────────────────────────┐
│              Browser (User)                  │
└────────────────────┬─────────────────────────┘
                     │ HTTP
┌────────────────────▼─────────────────────────┐
│         Laravel 11 (Filament Panel)          │
│  ┌──────────┐  ┌──────────┐  ┌───────────┐  │
│  │Resources │  │  Pages   │  │  Widgets  │  │
│  └────┬─────┘  └────┬─────┘  └─────┬─────┘  │
│       └─────────────┴──────────────┘         │
│                     │                        │
│  ┌──────────────────▼────────────────────┐   │
│  │         Eloquent Models + Scopes      │   │
│  └──────────────────┬────────────────────┘   │
│                     │                        │
│  ┌──────────────────▼────────────────────┐   │
│  │           MySQL 8.0 Database          │   │
│  └───────────────────────────────────────┘   │
└──────────────────────────────────────────────┘
```

---

## 📁 Directory Map

```
ieepis/
├── app/
│   ├── Console/Commands/      # Artisan commands (auto-registered in L11)
│   ├── Filament/
│   │   ├── Pages/             # Custom Filament pages (e.g. Dashboard)
│   │   ├── Resources/         # CRUD resources (one per model)
│   │   │   └── {Model}Resource/
│   │   │       └── Pages/     # ListX, CreateX, EditX
│   │   └── Widgets/           # Dashboard widgets (charts, stats)
│   ├── Http/
│   │   ├── Controllers/       # Web/API controllers (minimal; Filament handles most)
│   │   └── Middleware/        # HTTP middleware
│   ├── Models/                # Eloquent models (one file per entity)
│   ├── Notifications/         # Laravel notification classes
│   ├── Observers/             # Model observers for side effects
│   ├── Providers/             # Service providers
│   └── Scopes/                # Eloquent global scopes
│
├── bootstrap/
│   └── app.php                # L11 middleware + exception + routing config
│
├── config/                    # Laravel config files
├── database/
│   ├── factories/             # Model factories for tests/seeds
│   ├── migrations/            # Database schema history
│   └── seeders/               # Data seeders
│
├── resources/
│   ├── css/                   # App CSS (PostCSS)
│   ├── js/                    # App JS
│   └── views/                 # Blade templates
│
├── routes/
│   ├── web.php                # Web routes
│   ├── api.php                # API routes (minimal)
│   └── console.php            # Scheduled commands
│
├── tests/
│   ├── Feature/               # Feature tests (HTTP + Livewire)
│   └── Unit/                  # Unit tests (pure PHP logic)
│
└── .agents/
    └── skills/                # Domain-specific AI agent skills
```

---

## 🗄️ Data Model Relationships

```
divisions
  └── districts
        └── schools
              ├── employees              ← personnel directory
              ├── equipment              ← inventory core
              │     ├── equipment_assignments  ← accountability trail
              │     └── documents              ← PAR, ICS, IAR, etc.
              ├── documents              ← school-level documents
              ├── tickets                ← support tickets
              └── internet_connections   ← connectivity records

users                                   ← admin panel access (Filament)
approved_users                          ← whitelist for registration
```

### Key Relationship Rules
- **Equipment accountability** is ONLY tracked through `equipment_assignments` — never via a foreign key on `equipment`.
- Only **one active assignment** per equipment at a time (enforced in model/observer).
- All core models use **soft deletes** — deleted records are recoverable via `.trashed()` scope.
- All mutations to `schools`, `employees`, `equipment` are **audited** via Spatie ActivityLog.

---

## 🔐 Authorization Model

| Role | Scope |
|------|-------|
| `super_admin` | All data — no restrictions |
| `sdo_admin` | All schools in the division |
| `school_admin` | Own school only (enforced via global scope) |
| `technician` | Tickets + equipment status updates |
| `viewer` | Read-only on all resources |

Roles are managed by **Spatie Laravel Permission**. Check `app/Providers/AppServiceProvider.php` and each Resource's `canViewAny()` / policy for gate definitions.

---

## 🔄 Request Lifecycle

1. User hits `/admin/*` → Filament panel router
2. Filament Resource is loaded (e.g. `EquipmentResource`)
3. Global Scopes applied (e.g. school-scoped data for `school_admin`)
4. Livewire renders the form/table component
5. On mutation → Observer runs → ActivityLog entry created
6. Spatie Permission gates checked at Resource level

---

## 🧩 Key Design Decisions

See `DECISIONS.md` for the full rationale. Quick summary:

- **No dedicated API** — Filament + Livewire handles CRUD; no REST/GraphQL layer needed for this use case.
- **Assignment table pattern** — Equipment accountability is a many-snapshots-in-time pattern, not a simple FK. The `equipment_assignments` table is the source of truth.
- **FilamentPHP for admin** — Chosen for its rich CRUD scaffolding, built-in Livewire integration, and DepEd-admin-friendly UI.
- **School-scoped global scope** — Prevents `school_admin` users from ever seeing other schools' data at the query level.
