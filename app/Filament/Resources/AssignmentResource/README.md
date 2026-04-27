# Equipment Assignments Module

**Source of truth for ICT equipment accountability.** Equipment has no `employee_id` FK — every assignment, transfer, and return is a row in `equipment_assignments`.

---

## Write Path

**`app/Services/AssignmentService.php` is the only sanctioned write path.**

| Method | Purpose |
|---|---|
| `issue(array $data, User $actor)` | Open a new assignment. Asserts no active assignment exists for the equipment, then flips `equipment.accountability_status = 'assigned'`. |
| `transfer(EquipmentAssignment $current, array $newData, User $actor)` | Close current row (`returned_at`, `transaction_type = 'Transfer'`), open new one. |
| `return(EquipmentAssignment $current, array $data, User $actor)` | Close current row, flip `equipment.accountability_status = 'unassigned'`. |

All three wrap their work in `DB::transaction()` with `lockForUpdate()` on the equipment row to serialize concurrent operations.

## Schema

| Field | Type | Notes |
|---|---|---|
| `school_id` | FK → schools | Required; cascade on school delete |
| `equipment_id` | FK → equipment | Required; cascade |
| `employee_id` | FK → employees | Required; **restrict on delete** (preserve audit trail) |
| `custodian_id` | FK → employees, nullable | End user, when different from accountable officer |
| `assigned_by_id` | FK → users, nullable | The actor who created the row |
| `assigned_at` | date | When accountability took effect |
| `custodian_received_at` | date, nullable | Physical receipt date |
| `returned_at` | date, nullable | **`NULL` ⇔ active.** Single source of truth. |
| `transaction_type` | string | `Beginning Inventory \| Issuance \| Transfer \| Return` |
| `supporting_doc_type` | string, nullable | `PAR \| ICS \| RRSP \| RRPE` |
| `supporting_doc_no` | string, nullable | |
| `notes` | text, nullable | |
| `deleted_at` | timestamp, nullable | Soft delete — history is recoverable |

## Business Rules

1. **One active assignment per equipment** — enforced by `AssignmentService::issue()` inside a transaction with `lockForUpdate()`. Filament's create page calls the service; bypassing means losing the guarantee.
2. **Soft-deleted, not hard-deleted.** Filament resources expose **no `DeleteAction`** for assignments. To end an assignment, use the "Return Equipment" header action on the edit page (which calls `AssignmentService::return()`).
3. **Cross-school records are rejected.** `AssignmentService::issue()` verifies `employee.school_id === equipment.school_id`.
4. **Transaction types** are fixed enum values; do not accept free-text.
5. **Active state** is derived from `returned_at IS NULL` only. There is no `is_active` column.

## Filament Surface

| Page | Behavior |
|---|---|
| `AssignmentResource` index/create | Create flows through `AssignmentService::issue()` |
| `AssignmentResource` edit | Header `Return Equipment` action; field edits saved via standard Filament path (does not change lifecycle) |
| `EquipmentResource` / `SchoolResource` / `EmployeeResource` relation managers | **Read-only history views.** No create/edit/delete actions. |

## Related Files

- `app/Models/EquipmentAssignment.php`
- `app/Services/AssignmentService.php`
- `app/Filament/Resources/AssignmentResource.php`
- `app/Filament/Resources/AssignmentResource/Pages/{CreateAssignment,EditAssignment}.php`
- `app/Filament/Resources/{Equipment,School,Employee}Resource/RelationManagers/AssignmentsRelationManager.php`
- `database/migrations/2024_01_01_000004_create_related_tables.php` — `equipment_assignments` schema
