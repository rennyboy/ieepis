# Schools Module

**Top-level organizational unit** — all data in IEEPIS is scoped under a School.

---

## Responsibilities

- Store school profile: name, code, district, governance level, PSGC codes
- Geographic data: coordinates, GIDCA classification, remote school flag
- School head and admin contacts
- Parent-of: Employees, Equipment, Documents, Tickets, Internet Connections

## Public API (Key Model Methods)

- `School::employees()` — all staff assigned to this school
- `School::equipment()` — all ICT devices at this school
- `School::activeTickets()` — open/in-progress tickets
- `School::internetConnections()` — ISP connectivity records

## Key Fields

| Field | Description |
|-------|-------------|
| `name` | Full school name |
| `school_id` | DepEd school ID |
| `district_id` | Foreign key to districts |
| `governance_level` | Public / Private |
| `school_head` | Name of school principal |
| `latitude`, `longitude` | Map coordinates |
| `psgc_code` | Philippine Standard Geographic Code |
| `gidca` | GIDCA classification |
| `is_remote` | Remote school flag |

## Dependencies

- `District` — belongs to a district
- `Employee` — has many employees
- `Equipment` — has many equipment
- `Document` — has many documents
- `Ticket` — has many support tickets
- `InternetConnection` — has many connectivity records

## Authorization Notes

- `super_admin` and `sdo_admin` can see all schools.
- `school_admin` is restricted to their own school via **global Eloquent scope**.
- Never bypass the school scope without an explicit, documented reason.

## Related Files

- `app/Models/School.php`
- `app/Filament/Resources/SchoolResource.php`
- `app/Scopes/` — global school scope definition
