# Tickets Module

**Support ticket tracking** for ICT equipment issues and service requests.

---

## Responsibilities

- Accept and track ICT support requests from school staff
- Auto-generate unique ticket numbers (format: `TKT-YYYY-XXXX`)
- Track priority, status, and resolution timeline
- Link tickets to specific schools, equipment, and employees (reporter + technician)

## Public API (Key Model Methods)

- `Ticket::open()` — scope: non-closed tickets
- `Ticket::generateTicketNumber()` — auto-increments per year, called by Observer
- `Ticket::isResolved()` — returns `bool`
- `Ticket::assignTechnician(User $technician)` — assigns and sets status to In-Progress

## Key Fields

| Field | Description |
|-------|-------------|
| `ticket_number` | Auto-generated `TKT-YYYY-XXXX` — do not override |
| `school_id` | Originating school |
| `equipment_id` | Affected device (nullable — can be a general request) |
| `reporter_id` | Employee who filed the ticket |
| `assigned_technician_id` | User (technician role) handling the ticket |
| `priority` | Low / Medium / High / Critical |
| `status` | Open / In-Progress / Pending / Resolved / Closed |
| `description` | Problem description |
| `resolution_notes` | How it was resolved |
| `resolved_at` | Timestamp of resolution |

## Business Rules

1. `ticket_number` is **auto-generated** by an Observer — never set this manually in forms.
2. Once a ticket is **Closed**, it should not be re-opened without a new ticket.
3. Tickets are **soft-deleted** — never hard-delete.
4. Priority escalation is manual — no automatic escalation logic currently.

## Related Files

- `app/Models/Ticket.php`
- `app/Filament/Resources/TicketResource.php`
- `app/Observers/` (ticket number generation)
