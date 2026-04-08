# Equipment Module

**Core module of IEEPIS** — ICT inventory tracking per DepEd school division.

---

## Responsibilities

- Store all IEEPIS-standard fields for every ICT device (computers, printers, peripherals, software)
- Track device condition, functional status, and accountability status
- Generate QR codes for physical equipment tagging
- Classify devices as DCP (DepEd Computerization Program) or Non-DCP
- Link to acquisition documents (OR, SI, DR, IAR, RRSP)
- Support high-value (≥ ₱50,000) and low-value categories

## Public API (Key Model Methods)

- `Equipment::activeAssignment()` — get the currently active `EquipmentAssignment`
- `Equipment::assignTo(Employee $officer, Employee $endUser, array $data)` — create new assignment, close previous
- `Equipment::isAssigned()` — returns `bool`
- `Equipment::scopeBySchool($query, School $school)` — school-scoped query

## Key Fields

| Field | Description |
|-------|-------------|
| `property_no` | Official DepEd property number |
| `old_property_no` | Previous/bundled property number |
| `serial_no` | Manufacturer serial number |
| `item_type` | Device Type / Equipment / Hardware / Software / Peripherals |
| `is_dcp` | DepEd Computerization Program flag |
| `dcp_package` | DCP batch name and year |
| `category` | High-Value (≥₱50k) or Low-Value |
| `gl_sl_code` | Subsidiary Ledger code (COA compliance) |
| `uacs_code` | Unified Accounts Code Structure |
| `condition` | Good / Fair / Poor / Unserviceable |
| `functional_status` | Functional / Non-Functional |
| `accountability_status` | Normal / Transferred / Stolen / Lost / Damaged / For Disposal |
| `warranty_expiry` | Date — alert triggers 90 days before |

## Dependencies

- `School` — equipment belongs to a school
- `EquipmentAssignment` — accountability history (not a FK on this table)
- `Document` — linked acquisition and issuance documents
- `Ticket` — support issues for this device
- `Observer` — auto-generates QR code on creation

## Important Rules

1. **Never add `employee_id` to this table.** Accountability goes through `equipment_assignments`.
2. **QR codes are auto-generated** — do not manually generate in Resource forms.
3. **Soft deletes** — equipment is never permanently deleted.
4. **Audit logged** — all changes tracked via Spatie ActivityLog.

## Filament Resource

`app/Filament/Resources/EquipmentResource.php` — use this as the gold-standard example for all other resources.

## Related Files

- `app/Models/Equipment.php`
- `app/Models/EquipmentAssignment.php`
- `app/Filament/Resources/EquipmentResource.php`
- `app/Observers/` (QR code generation)
- `database/migrations/*_create_equipment_table.php`
