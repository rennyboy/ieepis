# Employees / Personnel Module

**Personnel directory** for all Teaching and Non-Teaching DepEd staff per school.

---

## Responsibilities

- Store complete employee profile compliant with DepEd HR records
- Track employment type (Teaching / Non-Teaching), position, and department
- Flag OIC designations and Non-DepEd funded personnel
- Track separation: cause, date, transferred from/to
- Show all equipment the employee is accountable for (via assignments)

## Public API (Key Model Methods)

- `Employee::activeAssignments()` — equipment currently under this person's accountability
- `Employee::isSeparated()` — returns `bool`
- `Employee::isOIC()` — returns `bool`
- `Employee::fullName()` — returns formatted "Last, First MI. Suffix"

## Key Fields

| Field | Description |
|-------|-------------|
| `employee_id` | DepEd employee ID |
| `last_name`, `first_name`, `middle_name`, `suffix` | Full name components |
| `position` | Job title |
| `department` | Department/office |
| `is_teaching` | Boolean — Teaching vs Non-Teaching |
| `employment_type` | Permanent / Temporary / Contract of Service |
| `is_oic` | Officer-in-Charge flag |
| `is_non_deped_funded` | SUC-funded / LGU-funded personnel flag |
| `separation_cause` | Resigned / Retired / Transferred / AWOL / Deceased |
| `separation_date` | Date of separation |
| `transferred_to` | School transferred to (if applicable) |

## Dependencies

- `School` — belongs to a school
- `EquipmentAssignment` — this employee's accountability records
  - `accountable_officer_id` on assignments — responsible officer
  - `end_user_id` on assignments — physical custodian/end user

## Authorization Notes

- `school_admin` can only view employees from their own school (global scope).
- `sdo_admin` and `super_admin` see all employees across all schools.

## Related Files

- `app/Models/Employee.php`
- `app/Filament/Resources/EmployeeResource.php`
