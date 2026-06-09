# Tasks

> Distilled current-state view. Full historical roadmap lives in root `TASKS.md`.

## In Progress
- [ ] Comprehensive PHPUnit coverage (Equipment CRUD, one-active-assignment invariant, school-scope isolation, role gates)

## This Sprint (P1)
- [ ] Bulk QR code export (per school/room sheets)
- [ ] PAR/ICS PDF generation (printable accountability forms)
- [ ] Warranty expiry notification (<90 days, email)
- [x] Equipment import via Excel template
- [ ] Refactor `DcpDistributionData.php` to move aggregations to SQL (High Priority)

## This Week (P2)
- [ ] Maintenance log (repairs/service history per equipment) — wire `MaintenanceLog` stub model
- [ ] Equipment transfer wizard (multi-step + auto document)
- [ ] Audit report export (COA-compliant Excel/PDF)

## Done
- [x] PPE Physical Count module (draft, completion, local approval flow, exports, tests)
- See root `TASKS.md` `## ✅ Completed` section
