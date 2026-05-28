# IEEPIS — Master Reference

> Last regenerated: 2026-04-28
> Audience: any new developer joining the project, or any future AI agent that needs ground truth.
> Source: synthesized from codebase + `.ai/{memory,decisions,handoff}.md` + `SecondBrain/Errors/`.

---

## 1. Overview

**IEEPIS** = ICT Equipment and Employee Profile Information System.

A school-division-level inventory system for the **DepEd Philippines** (Department of Education). It tracks:

- ICT equipment (laptops, projectors, network gear, etc.) with full accountability history (PAR / ICS / RRSP / RRPE supporting docs)
- Employee directory (teaching + non-teaching)
- School internet connectivity per school
- Support tickets and maintenance logs
- Approval/whitelist workflow for who can register

Scoped to a single **Schools Division Office (SDO)**. Each School is a tenant under that SDO.

**Owner:** rennyboyjr@gmail.com (single dev)

---

## 2. Tech Stack — what / why / where / keep

### Backend

| Package | Version | Why | Where used | Keep? |
|---|---|---|---|---|
| **PHP** | 8.4 | Constructor promotion, enums, readonly | everywhere | Keep |
| **Laravel** | 11 | Modern Laravel — `bootstrap/app.php`, no kernel | framework | Keep |
| **FilamentPHP** | v3 | Admin panel — saves months of UI work | `app/Filament/` | Keep |
| **Livewire** | v3 | Filament's runtime | indirect | Keep |
| **Tailwind CSS** | v3 | Filament's styling | `resources/css/` | Keep |
| **Spatie Permission** | latest | Roles: super-admin, sdo-admin, school-admin, technician, viewer | `User` model + Filament `can()` methods | Keep |
| **Spatie ActivityLog** | latest | Audit trail on Equipment, Employee, Ticket, School, EquipmentAssignment | Models with `LogsActivity` trait | Keep, but add `logExcept()` for PII |
| **Laravel Socialite** | latest | Google OAuth | `app/Http/Controllers/Auth/GoogleController.php` | Keep |
| **barryvdh/laravel-dompdf** | ^2.2 | PDF generation (PAR/ICS forms, bulk lists) | `app/Http/Controllers/*PdfController.php` + PWA M1 | Keep |
| **simplesoftwareio/simple-qrcode** | ^4.2 | Equipment QR code payload | `Equipment` boot hook | Keep |
| **maatwebsite/excel** | latest | Excel exports/imports | not yet wired into UI | Keep — wire to admin UI |
| **openai-php/laravel** | latest | (was for AI features?) | **UNUSED in current code** | **Remove** (`composer remove openai-php/laravel`) |

### Database

| Tech | Why | Notes |
|---|---|---|
| **PostgreSQL 14+** (current) or **MySQL 8.0+** | Both supported per Decision 010 | Code must be DB-agnostic — use Schema builder, no driver-specific SQL. The `Ticket::generateTicketNumber()` MySQL `GET_LOCK()` is a known violation pending fix |

### Dev tooling

| Tool | Purpose | Notes |
|---|---|---|
| **PHPUnit 11** | Tests (NOT Pest — Decision 008) | 3 test files at present; major coverage gap |
| **Laravel Pint** | Code formatter | Installed; no pre-commit hook yet |
| **Laravel Telescope** | Local debugging UI | `TELESCOPE_ENABLED=false` in `.env`. Migration exists; tables not migrated. Flip the env var + run `php artisan migrate` to enable |
| **Laravel Boost** | MCP for AI tools (Claude Code, Cursor, Kilo, OpenCode) | `composer.json` dev dep + `opencode.json` |
| **Faker** | Test factories | Standard Laravel |
| **Mockery** | Test mocking | Standard Laravel |

### Removed / never used

- ~~Laravel Sail / Docker~~ — superseded by Decision 014. Local dev only.
- ~~`.aider.conf.yml`, `.windsurfrules`, `.codeiumignore`, `.jetbrains/`~~ — removed earlier today (these tools aren't in use).

---

## 3. Important Files Explained

### Routes

| File | Purpose | Key logic | Risks | Notes |
|---|---|---|---|---|
| [`routes/web.php`](routes/web.php) | Public + admin entry | Redirects `/` → `/admin`. PDF bulk export endpoints. Google OAuth routes (rate-limited to 10/min). | PDF endpoints have `auth` middleware only — no role check (S1). | Add `role:` middleware. |
| [`routes/api.php`](routes/api.php) | REST API (planned) | **Empty.** PWA M1 will populate with Sanctum-protected endpoints (Decision 013). | — | Touch on PWA milestone start. |
| [`routes/auth.php`](routes/auth.php) | Login/logout/password reset | Filament's auth pages override most of this. | — | Standard. |

### Controllers

| File | Purpose | Risks |
|---|---|---|
| [`app/Http/Controllers/EquipmentPdfController.php`](app/Http/Controllers/EquipmentPdfController.php) | Bulk equipment PDF | Synchronous dompdf — slow for >1k devices. Move to queued job in PWA M5. |
| [`app/Http/Controllers/EmployeePdfController.php`](app/Http/Controllers/EmployeePdfController.php) | Bulk employee PDF | Same. |
| [`app/Http/Controllers/Auth/GoogleController.php`](app/Http/Controllers/Auth/GoogleController.php) | Google OAuth flow | **Missing `email_verified` check (C2).** |

### Models

| File | Purpose | Key logic | Notes |
|---|---|---|---|
| [`app/Models/User.php`](app/Models/User.php) | Auth identity | `$with = ['employee']` auto-eager-loads. Accessor delegates `name`/`school_id`/`division_id` to Employee. `school_id` is a **hybrid column** — direct read first, employee fallback. | Don't access `$this->school_id` inside the accessor — recursion (see `SecondBrain/Errors/2026-04-28-auth-with-eager-load-recursion.md`). Use `$this->attributes['school_id']`. |
| [`app/Models/Employee.php`](app/Models/Employee.php) | Canonical personal/org data | `full_name`, `school_id`, `user_id` (1:1 to User, nullable). SchoolScope global. | Identity unification source of truth (Decision 011). |
| [`app/Models/Equipment.php`](app/Models/Equipment.php) | Inventory item | Boots SchoolScope. QR auto-generated on create/update via boot. `accountability_status` denormalized — flipped by AssignmentService. `sharedDocument()` returns latest doc by `document_date DESC, created_at DESC`. | NEVER add `employee_id` FK — accountability lives in `equipment_assignments` (Decision 002). |
| [`app/Models/EquipmentAssignment.php`](app/Models/EquipmentAssignment.php) | Accountability ledger | One row per assign/transfer/return event. `returned_at IS NULL` ⇔ active. SoftDeletes for safe history. Spatie ActivityLog. | Writes ONLY through `AssignmentService` — never direct (Decision 002). |
| [`app/Models/Document.php`](app/Models/Document.php) | PAR/ICS/etc. file attachments | `file_path`, `mime_type`, `document_type` (cast to `App\Enums\DocumentType` enum). Soft deletes. | PWA M4 will add polymorphic `attachable` for ticket attachments. |
| [`app/Models/Ticket.php`](app/Models/Ticket.php) | Issue reports | Auto-numbers as `TKT-YYYY-NNNNN`. **Currently uses MySQL `GET_LOCK()` — broken on Postgres (B3).** | M4 of PWA plan brings the cross-driver fix forward. |
| [`app/Models/School.php`](app/Models/School.php) | Tenant unit | Cascades to equipment, documents, tickets, employees, equipment_assignments | Cascade is risky (C3). |
| [`app/Models/ApprovedUser.php`](app/Models/ApprovedUser.php) | Registration whitelist | Email + role + status (pending/approved/rejected). New `school_id` column added 2026-04-28. | `division_id` FK references `districts` (B1) — verify intent. |
| [`app/Models/MaintenanceLog.php`](app/Models/MaintenanceLog.php) | Equipment service history | Cascades on technician_id (B4). | Half-implemented (resource is barebones). |
| [`app/Models/Notification`] | (uses Laravel built-in `notifications` table) | Filament's bell icon | `data` column is `json` post-fix (Decision 010 driven). |

### Middleware

| File | Purpose |
|---|---|
| [`app/Http/Middleware/EnsureAccountIsApproved.php`](app/Http/Middleware/EnsureAccountIsApproved.php) | Blocks pending/rejected users from `/admin` |

### Services

| File | Purpose |
|---|---|
| [`app/Services/AssignmentService.php`](app/Services/AssignmentService.php) | **Sole sanctioned write path for equipment_assignments.** `issue/transfer/return` methods — `DB::transaction` + `lockForUpdate()` on equipment. Asserts no active assignment, asserts same-school invariant, denormalizes `equipment.accountability_status`. |

### Observers

| File | Purpose |
|---|---|
| [`app/Observers/TicketObserver.php`](app/Observers/TicketObserver.php) | Filament notifications on ticket created/status-change. Email queued on resolve. |

### Filament resources (the admin UI surface)

| Resource | Group | Purpose |
|---|---|---|
| `EquipmentResource` | ICT Inventory | CRUD for devices |
| `AssignmentResource` | ICT Inventory | View accountability history; calls AssignmentService for create/return |
| `MaintenanceLogResource` | ICT Inventory | Repair/service log per equipment |
| `EmployeeResource` | People | Personnel directory |
| `UserResource` | Settings | Auth account management — picks an Employee |
| `SchoolResource` | Organization | Top-level tenant |
| `DistrictResource` | Organization | School groupings |
| `DocumentResource` | Documents & Tickets | PAR/ICS/etc. uploads |
| `TicketResource` | Documents & Tickets | Issue reports |
| `InternetConnectionResource` | Organization | ISP per school |
| `ApprovedUserResource` | Administration | Registration whitelist |

### Configuration

| File | Notes |
|---|---|
| `config/permission.php` | Spatie Permission settings |
| `config/services.php` | Google OAuth credentials |
| `config/telescope.php` | Telescope config (gated to local) |
| **No `config/app.php`** | Laravel 11 default — file is optional. Don't restore it; only Telescope's `:install` legacy code path needs it. |

### Tests

| File | Coverage |
|---|---|
| [`tests/Feature/EquipmentSharedDocumentTest.php`](tests/Feature/EquipmentSharedDocumentTest.php) | 5 cases — sharedDocument() / hasSharedDocument() |
| [`tests/Feature/BootSmokeTest.php`](tests/Feature/BootSmokeTest.php) | Boot + basic /admin response |
| [`tests/TestCase.php`](tests/TestCase.php) | Standard Laravel test base |

**Coverage gap is the largest quality risk in the project.**

---

## 4. Data Flow Map

```
┌─────────────────────────────────────────────────────────────┐
│  USER (browser)                                              │
└──────────────┬──────────────────────────────────────────────┘
               │ HTTP request, session cookie
               ▼
┌─────────────────────────────────────────────────────────────┐
│  Laravel 11 — bootstrap/app.php → middleware stack          │
│   - Web/Auth/Approval middleware                             │
│   - Spatie Permission cache (per request)                    │
└──────────────┬──────────────────────────────────────────────┘
               │ resolved User (with employee eager-loaded via $with)
               ▼
┌─────────────────────────────────────────────────────────────┐
│  Filament Panel (Livewire)                                   │
│   - Resources: form/table state in Livewire components       │
│   - $user->school?->name etc. delegate via accessors         │
└──────────────┬──────────────────────────────────────────────┘
               │ Eloquent queries (auto-scoped via SchoolScope)
               ▼
┌─────────────────────────────────────────────────────────────┐
│  Models                                                      │
│   - Equipment / Employee / Document / Ticket: SchoolScope    │
│   - EquipmentAssignment: SchoolScope + AssignmentService    │
│   - LogsActivity → activity_log table                        │
└──────────────┬──────────────────────────────────────────────┘
               │ SQL
               ▼
┌─────────────────────────────────────────────────────────────┐
│  PostgreSQL                                                  │
│   - 23 tables; FK constraints; cascade on School deletes     │
└─────────────────────────────────────────────────────────────┘

Side channels:
  - Filament notifications → notifications table (data: json post-fix)
  - File uploads → storage/app/public/schools/{school_id}/...
  - QR codes → Equipment.qr_code column (string payload, not image)
  - PDFs → dompdf renders Blade view → ->download() to user
```

---

## 5. Authentication Map

```
┌─────────────────┐
│ GET /admin/login│
└────────┬────────┘
         │
         ├──── Filament Login (Livewire form) ────────────────┐
         │                                                     │
         │   email + password                                  │
         │   ↓                                                 │
         │   throttle:auth (5/min per IP+email)                │
         │   ↓                                                 │
         │   Auth::attempt() → SessionGuard                    │
         │   ↓                                                 │
         │   $user->canAccessPanel():                          │
         │     approval_status == 'approved' && hasRole(*)     │
         │   ↓                                                 │
         │   redirect /admin                                   │
         │                                                     │
         └──── Google OAuth /auth/google ──────────────────────┤
                                                                │
             throttle:oauth (10/min per IP)                     │
             ↓                                                  │
             Socialite::driver('google')->user()                │
             ↓                                                  │
             Check ApprovedUser whitelist by email              │
             ↓                                                  │
             If approved: User::create or User::find            │
             ↓                                                  │
             Auto-link to Employee by email (if exists)         │
             ↓                                                  │
             Auth::login()                                       │
                                                                │
                                                                ▼
                                              [authenticated]
                                              SchoolScope reads
                                              $user->school_id
                                              (column or
                                               employee fallback)
```

**Roles:** super-admin (cross-school), sdo-admin (cross-school), school-admin (their school), technician (their school + maintenance), viewer (read-only their school).

**Sessions:** file-backed (`storage/framework/sessions/`), 120-min lifetime, NOT encrypted (S3 — fix this).

---

## 6. Deployment Notes

> **Currently no production deploy.** Local-only dev (Decision 014). When you do deploy:

### Pre-flight

1. **`.env` for production:**
   - `APP_ENV=production`, `APP_DEBUG=false`
   - `APP_KEY` regenerated with `php artisan key:generate`
   - `DB_*` pointed at production Postgres (or MySQL — both supported)
   - `SESSION_ENCRYPT=true`
   - `SESSION_DRIVER=redis` (with Redis), `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`
   - `MAIL_*` set to a real SMTP (currently `MAIL_MAILER=log`)
   - `TELESCOPE_ENABLED=false` (or true, with the gate populated)
   - `SEED_SUPER_ADMIN_PASSWORD=<strong>` if running the seeder
   - `APP_PWA_ORIGIN=https://your-pwa-domain` (for the PWA's CORS allowlist when M1 ships)

2. **Run migrations:**
   ```bash
   php artisan migrate --force
   ```

3. **Seed only if first deploy:**
   ```bash
   php artisan db:seed --force
   ```

4. **Optimize:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

5. **Storage:**
   ```bash
   php artisan storage:link
   chmod -R 775 storage bootstrap/cache
   ```

6. **Queue worker** (when M5 brings real queues):
   ```bash
   php artisan queue:work --queue=default --sleep=3 --tries=3
   ```
   Use Supervisor to keep it running.

### Rollback

- Migrations have `down()` for everything.
- Revert deployment via standard git rollback + `php artisan migrate:rollback`.

### Backups

- **PostgreSQL nightly dump** — must include `equipment_assignments` (the audit ledger), `activity_log`, `documents` metadata.
- **Storage backups** — `storage/app/public/schools/*/documents/` (uploaded PARs, ICSs).

### Monitoring

- Health endpoint: `GET /up` returns `{"status":"ok","system":"IEEPIS","version":"1.0.0"}`. Wire this into your uptime monitor.
- Logs: `storage/logs/laravel.log` — rotate with logrotate.
- (When ready) Telescope at `/telescope` — populate the email whitelist in `TelescopeServiceProvider::gate()`.

---

## 7. Recommended Next Improvements (priority-ordered)

### Tier 0 — before any deploy (Critical)
1. Add `can()` and `getEloquentQuery()` to `AssignmentResource` (C1)
2. Add `email_verified` check in `GoogleController` (C2)
3. Review `cascadeOnDelete` on the 5 school-cascading tables (C3)
4. `SESSION_ENCRYPT=true` in production (S3)

### Tier 1 — first week
5. Test coverage v1 — auth flow + AssignmentService + SchoolScope isolation
6. Replace `Ticket::generateTicketNumber()` `GET_LOCK()` with `ticket_sequences` table (B3)
7. Activity log retention + LogExcept for PII (S6)
8. Drop `school_id` from `User::$fillable` (S2)
9. Fix the silent form bugs (`remarks`, `property_number`) (B2, B6)
10. Remove unused `openai-php/laravel`

### Tier 2 — first month
11. CI pipeline (Pint + Larastan + PHPUnit)
12. PWA M1 + M2 (per `~/.claude/plans/i-am-planning-to-zany-reddy.md`)
13. Soft-delete consistency review
14. Cache layer for `Role::all()` and other static lookups

### Tier 3 — before scale
15. Domain-driven restructuring (`app/Domain/...`)
16. Redis for cache/queue/session
17. S3-compatible storage (Cloudflare R2 per Decision 013 plan)
18. Move large Filament resource files into form/table builder classes
19. Re-enable Telescope with proper gates

---

## 8. Where to look for more context

| Question | File |
|---|---|
| What did we decide and why? | [`.ai/decisions.md`](.ai/decisions.md) — 12 active decisions |
| What's the current state of work? | [`.ai/handoff.md`](.ai/handoff.md) |
| What are the project rules? | [`AGENTS.md`](AGENTS.md) (single source for AI tools) |
| What modules exist? | [`.ai/memory.md`](.ai/memory.md) + [`.ai/memory-modules.md`](.ai/memory-modules.md) |
| Did we hit this error before? | [`SecondBrain/Errors/INDEX.md`](SecondBrain/Errors/INDEX.md) |
| What did we do today / yesterday? | [`SecondBrain/Daily Notes/`](SecondBrain/Daily Notes/) |
| What does the PWA roadmap look like? | `~/.claude/plans/i-am-planning-to-zany-reddy.md` |

---

## FINAL SCORE — at the time of this audit

| Dimension | Score | Reasoning |
|---|---|---|
| **Security** | 6 / 10 | Auth works; rate-limiting in place; SchoolScope enforces tenancy. But authorization is patchy (3 critical), file uploads under-validated, sessions unencrypted, default seeder passwords are sane only because they're env-gated |
| **Maintainability** | 5 / 10 | Filament conventions followed, AssignmentService exemplary, `.ai/` workspace excellent, decisions ledger alive. But 0 controllers under Api/, only 1 service class, 3 tests total, no CI, large resource files |
| **Scalability** | 6 / 10 | Indexes mostly present, DB-agnostic discipline, hot paths measured at 172-267ms. But no caching layer, queue is `sync`, no S3, activity_log unbounded, cascade chains threaten data |
| **Code Quality** | 7 / 10 | Reads well, conventions consistent, recent code is sharp. Older code has mixed quote styles, `whereIn([single])` quirks, dead `openai-php` dependency |
| **Stability** | 7 / 10 | Recursion bug fixed and captured, AssignmentService transactional, Filament battle-tested. But 3 tests means no regression net — next refactor could break silently |
| **Overall** | **6.2 / 10** | Production-blocked but recoverable in 2-3 weeks of focused work |
