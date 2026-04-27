# Modules — IEEPIS

> Split from `.ai/memory.md` to keep the index file under the 50-line cap. Full machine-readable relationship map in `.ai/context.json`.

## Module Map

| Domain | Model | Resource | Notes |
|---|---|---|---|
| Schools | `School` | `SchoolResource` | Top-level org unit |
| Personnel | `Employee` | `EmployeeResource` | Teaching + non-teaching; **canonical identity record** (User links here via `employees.user_id`) |
| Equipment | `Equipment` | `EquipmentResource` | DCP/non-DCP, has QR |
| Assignments | `EquipmentAssignment` | `AssignmentResource` | Accountability trail; writes only via `AssignmentService` |
| Documents | `Document` | `DocumentResource` | PAR, ICS, IAR, DR, OR, SI, WMR, RRSP, RRPE |
| Tickets | `Ticket` | `TicketResource` | Support tickets |
| Connectivity | `InternetConnection` | `InternetConnectionResource` | ISP per school |
| Districts | `District` | `DistrictResource` | School grouping |
| Users | `User` | `UserResource` | Auth shell — delegates name/school/division reads to `employee` relation |
| Access Control | `ApprovedUser` | `ApprovedUserResource` | Registration whitelist |

## Service classes (sole sanctioned write paths)

| Service | Owns |
|---|---|
| `App\Services\AssignmentService` | Equipment assignment lifecycle (`issue`, `transfer`, `return`) |
