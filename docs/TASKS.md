# TASKS.md — Current Tasks & Roadmap

> **For AI Agents**: Check this file before starting any task. The user may reference items here. Update this file when tasks are completed.

---

## ✅ Completed

- [x] Core Laravel 11 + FilamentPHP v3 setup
- [x] Schools module (CRUD + Relation Managers)
- [x] Employee/Personnel module
- [x] Equipment module (full IEEPIS fields)
- [x] Equipment Assignments (accountability trail)
- [x] Documents module (PAR, ICS, IAR, etc.)
- [x] Internet Connectivity module
- [x] Support Tickets module
- [x] Districts module
- [x] Role-based access (Spatie Permission)
- [x] School-scoped global scope for `school_admin`
- [x] Audit logging (Spatie ActivityLog)
- [x] QR code auto-generation for equipment
- [x] Dashboard with widgets (stats, charts, tickets)
- [x] Google OAuth / Socialite integration
- [x] Approved Users registration whitelist
- [x] Hybrid Docker setup (local PHP + Docker services)
- [x] DCP Distribution Dashboard
- [x] IDE-agnostic AI agent setup (AGENT.md, ARCHITECTURE.md, DECISIONS.md, project.json, prompts/)

---

## 🔄 In Progress

- [ ] Comprehensive PHPUnit test coverage
  - Equipment CRUD tests
  - Assignment one-active-at-a-time enforcement test
  - School scope isolation test
  - Role-based access tests

---

## 📋 Backlog

### High Priority
- [ ] **Bulk QR Code Export** — Print QR code sheets per school/room
- [ ] **PAR/ICS PDF Generation** — Auto-generate printable accountability forms
- [ ] **Warranty Expiry Notification** — Email alert when warranty < 90 days
- [ ] **Equipment Import via Excel** — Bulk-upload IEEPIS data from spreadsheet template

### Medium Priority
- [ ] **Maintenance Log** — Track repairs and service history per equipment
- [ ] **Equipment Transfer Wizard** — Multi-step transfer with document generation
- [ ] **Audit Report Export** — COA-compliant Excel/PDF export
- [ ] **Speed Test Integration** — Embed speedtest widget on connectivity page

### Low Priority / Future
- [ ] **Mobile App API** — REST API layer if a mobile companion app is built
- [ ] **SMS Notifications** — Ticket updates via SMS (Semaphore/Globe integration)
- [ ] **School Location Map** — Interactive map showing all schools with pins
- [ ] **PSGC Data Sync** — Auto-sync barangay/municipality codes from PSA

---

## 🐛 Known Issues

- [ ] `MaintenanceLog` model exists but has no seeder or Resource — stub only
- [ ] `ReassignmentAudit` model is created but not fully wired to Resource
- [ ] `LIST OF EMPLOYEES 03.09.csv` at root — needs to be imported via seeder or moved to `storage/`
- [ ] Some legacy markdown docs at root can be archived to `/docs/` for cleanliness

---

## 📅 Maintenance Reminders

- Run `vendor/bin/sail artisan ieepis:check-warranties` weekly (or add to scheduler)
- Backup database before any migration in production
- Run `vendor/bin/sail artisan optimize` before each production deploy
