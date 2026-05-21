# Equipment Assignments Module

**Source of truth for ICT equipment accountability** — replaces a simple FK on the equipment table.

---

## Responsibilities

- Record every accountability transaction for a device
- Enforce that only ONE assignment is active per device at any point in time
- Track: who is accountable (officer), who uses it (end user), and what document authorizes it
- Support document references: PAR No., ICS No., RRSP, RRPE, WMR

## Public API (Key Model Methods)

- `EquipmentAssignment::active()` — scope: only active assignments
- `EquipmentAssignment::closeCurrentAndCreate(...)` — closes active, creates new in one transaction

## Key Fields

| Field | Description |
|-------|-------------|
| `equipment_id` | The device being assigned |
| `accountable_officer_id` | Employee responsible for the asset (PAR/ICS signatory) |
| `end_user_id` | Employee actually using the device (Custodian) |
| `transaction_type` | Beginning Inventory / Issuance / Transfer / Return |
| `document_reference` | PAR No., ICS No., etc. |
| `is_active` | `true` = current active assignment |
| `assigned_date` | Date accountability took effect |
| `receipt_date` | Date of physical receipt |

## Business Rules

1. **Only ONE active assignment per equipment** — enforced at model level. Attempting to create a second active assignment must close the first.
2. **Transaction types** are fixed enum values — do not accept free-text here.
3. This table is **not soft-deleted** — the history is immutable; old assignments stay as records.
4. **No `employee_id` on `equipment`** — this table is the only source of accountability.

## Related Files

- `app/Models/EquipmentAssignment.php`
- `app/Filament/Resources/AssignmentResource.php`
- `app/Filament/Resources/EquipmentResource/` — Relation Manager for assignments
