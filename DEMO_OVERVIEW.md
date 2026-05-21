# IEEPIS — Demo & Overview Guide

> **For presenters.** This document explains *what problem IEEPIS solves*, then walks
> through the app **feature by feature**. Each feature is framed as
> **Problem → Solution → What to show in the demo**, so anyone can present it
> without knowing the codebase.

---

## 1. The Elevator Pitch (say this first)

> **IEEPIS** (ICT Equipment and Employee Profile Information System) is a web
> application that lets a **DepEd School Division Office (SDO)** track every
> piece of ICT equipment, who is accountable for it, where it is, and what
> condition it's in — across **all schools in the division**, from one place.

It replaces scattered Excel files and paper property forms with a single,
audited, role-secured system.

---

## 2. The Problem We Solve

A DepEd School Division Office manages **thousands of ICT items** (laptops,
smart TVs, DCP packages) spread across **dozens of schools** and **hundreds of
employees**. Today this is typically run on:

- **Disconnected Excel sheets** — one per school, no single source of truth,
  constant version drift.
- **Paper accountability forms** (PAR / ICS / RRSP) — easy to lose, impossible
  to search, no history of who held an item before.
- **No visibility** — the SDO cannot answer "how many teaching laptops does
  District X have?" without manually tallying spreadsheets.
- **No accountability trail** — when equipment goes missing, there is no record
  of custody changes.
- **Manual DCP reporting** — the DepEd Computerization Program (DCP)
  distribution report is rebuilt by hand every time it's requested.

**IEEPIS fixes all of this** with one secured, auditable system.

| Pain | IEEPIS answer |
|---|---|
| Spreadsheets drift, no single truth | One central database, role-scoped |
| Paper PAR/ICS lost, no history | Digital assignment trail + auto-generated documents |
| "Who has this laptop now / before?" | Full custody & accountability history per item |
| DCP report rebuilt by hand | One-click DCP dashboard + PDF export |
| Anyone can edit anything | Per-school access control + approval workflow |
| No audit when things go wrong | Automatic activity log on every change |
| Field staff have no signal/internet | Offline QR scanning & sync |

---

## 3. Who Uses It (Roles)

| Role | Sees / can do |
|---|---|
| **Super Admin** (SDO ICT) | Everything, all schools, all settings |
| **SDO Admin** | Division-wide oversight & reporting |
| **School Admin** | **Only their own school's** data (enforced automatically) |
| **Technician** | Cross-school equipment & ticket handling (repairs) |

> **Key selling point:** a School Admin physically *cannot* see or touch
> another school's records — it's enforced at the database query level, not
> just hidden in the UI.

---

## 4. Features — One by One

For each feature: **the problem**, **how IEEPIS solves it**, and **what to
click during the demo**.

### 4.1 Equipment Inventory
- **Problem:** No authoritative list of what equipment exists, where, and in
  what condition.
- **Solution:** Every item is a record — type, brand, model, serial/property
  number, school, condition, DCP classification. A **QR code is generated
  automatically** the moment equipment is created.
- **Demo:** Open *Equipment* → show the list, filters by school/condition,
  open one item → point out the auto-generated QR code.

### 4.2 Equipment Assignment & Accountability (PAR / ICS / RRSP)
- **Problem:** Paper accountability forms get lost; nobody knows who held an
  item before.
- **Solution:** Equipment has a fixed **Accountable Officer** (the person who
  signed for it on delivery) recorded on the item itself, plus a rotating
  **Custodian/End-User** assignment trail. Every issuance, transfer, and
  return is recorded; the system enforces **one active assignment per item**
  so accountability can never be ambiguous.
- **Demo:** Open an item → show the Accountable Officer; go to *Assignments* →
  issue/transfer/return → show the history trail.

### 4.3 Employee Directory
- **Problem:** Staff lists live in HR spreadsheets, duplicated everywhere.
- **Solution:** One canonical employee record per person (teaching /
  non-teaching, school, employment type). Login accounts link to this single
  record — **no duplicate "person" data**.
- **Demo:** Open *Employees* → filter teaching vs non-teaching → show a
  profile; mention names normalize automatically on import.

### 4.4 Schools & Districts
- **Problem:** No structured org hierarchy to roll reporting up to.
- **Solution:** Schools grouped into Districts; every employee and item rolls
  up to a school. School Admins are locked to their school; delete is disabled
  for them so they can't remove their own school record.
- **Demo:** Open *Schools* → show district grouping; (optionally) log in as a
  School Admin to show the scoped view.

### 4.5 DCP Distribution Dashboard ⭐ (headline feature)
- **Problem:** The DepEd DCP distribution report (laptops for teaching/
  non-teaching, smart TVs vs. teacher population per district) is rebuilt by
  hand on demand — slow and error-prone.
- **Solution:** A live dashboard: stats overview, distribution charts,
  population vs. packages chart, and a per-district percentages table —
  computed straight from the inventory. **One-click "Export PDF"** produces a
  shareable report with the charts and tables embedded.
- **Demo:** Open *DCP Distribution* → walk the charts and the percentages
  table → click **Export PDF** → open the downloaded report. *(This is the
  strongest "wow" moment — lead with it or save it as the closer.)*

### 4.6 Support Tickets
- **Problem:** Equipment repair requests are made by phone/text with no
  tracking, no priority, no record of resolution.
- **Solution:** Schools file tickets against specific equipment, with
  priority (Low→Critical) and status workflow. Technicians and SDO admins are
  notified automatically; when a ticket is resolved, the school is notified
  it's ready for pickup. Priority sorts by real severity, not alphabetically.
- **Demo:** Open *Tickets* → create one against an item, set priority → show
  the notification → mark resolved → show the school-admin notification.

### 4.7 Documents (PAR, ICS, IAR, DR, OR, SI, WMR, RRSP, RRPE)
- **Problem:** Property documents are paper, unsearchable, easily lost.
- **Solution:** Documents are stored, typed, and linked to equipment/
  assignments — including auto-generated accountability forms.
- **Demo:** Open *Documents* → filter by type → show a document linked to its
  equipment.

### 4.8 Internet Connectivity Tracking
- **Problem:** SDO can't see which schools have internet, which ISP, what
  bandwidth.
- **Solution:** One connectivity record per school (ISP, plan, status).
- **Demo:** Open *Internet Connections* → show coverage across schools.

### 4.9 QR Scanning & Offline Field Operations
- **Problem:** Field staff inventory equipment in schools with **no internet
  signal**.
- **Solution:** Every item has a QR code; the app has a QR scanner page and an
  **offline equipment cache** that syncs when connectivity returns.
- **Demo:** Open the *QR Scanner* page → scan an item's code → show it pulls
  up the record (mention offline cache + later sync).

### 4.10 Access Control & Approval Workflow
- **Problem:** Open registration = anyone could get into division data.
- **Solution:** New logins must be **approved** (whitelist of approved users);
  roles are assigned per school. Google sign-in supported with throttling.
- **Demo:** Show *Approved Users* / *Users* → explain approval gate and
  per-school role assignment.

### 4.11 Automatic Audit Trail
- **Problem:** When equipment is mis-assigned or deleted, there's no record of
  who did what.
- **Solution:** Every change to Schools, Employees, and Equipment is logged
  automatically (who, what, when, old → new values). Records are
  **soft-deleted** — nothing is truly lost and can be restored.
- **Demo:** Edit an equipment field → show the activity log entry with the
  before/after values.

### 4.12 Bulk Import (Excel)
- **Problem:** Onboarding a school means entering hundreds of rows by hand.
- **Solution:** Excel import for employees and equipment, with a downloadable
  template, name normalization, and school-aware handling so items land in
  the correct school.
- **Demo:** Show the import button + template; mention the importer
  de-duplicates and normalizes data.

---

## 5. Cross-Cutting Strengths (mention if asked "why is this solid?")

- **Security by default** — per-school data isolation enforced at the query
  layer; every action authorized by policies; approval-gated registration.
- **Nothing is lost** — soft deletes everywhere + full activity audit log.
- **Single source of truth** — one employee record per person; one active
  accountability per item, enforced in code (not by convention).
- **Works in the field** — QR + offline cache for low/no-signal schools.
- **Portable** — runs on PostgreSQL *or* MySQL; containerized for deployment.
- **Reporting built in** — the DCP report that used to take hours is one click.

---

## 6. Suggested 10-Minute Demo Flow

1. **(1 min) Frame the problem** — "An SDO tracks thousands of ICT items
   across dozens of schools in spreadsheets and paper. Here's the cost of
   that." (Use the table in §2.)
2. **(1 min) Log in** — show the role-based landing; mention approval gate.
3. **(2 min) Equipment + Accountability** — open an item, show QR + the
   accountable officer, then the assignment history trail.
4. **(2 min) Per-school isolation** — switch to a School Admin view; show they
   only see their own school. (Strong trust point.)
5. **(2 min) Tickets** — file a repair ticket, set priority, resolve it, show
   the automatic notifications.
6. **(2 min) DCP Dashboard — the closer** — walk the charts + percentages,
   then **Export PDF** and open the generated report. End on this.

> **Tip:** If short on time, do steps 1, 3, and 6 only. The DCP PDF export is
> the single most memorable moment — always show it.

---

## 7. Presenter FAQ / Likely Questions

- **"Can a school see another school's data?"** No — it's blocked at the
  database query level for the School Admin role, not just hidden.
- **"What if someone deletes equipment by mistake?"** Records are
  soft-deleted and every change is in the audit log — it can be restored and
  you can see who did it.
- **"Does it work without internet in remote schools?"** Yes — QR scanning
  with an offline cache that syncs when back online.
- **"Can we move off this database later?"** It's DB-agnostic
  (PostgreSQL or MySQL), no vendor lock-in.
- **"Who can register?"** Only approved users; roles are assigned per school.
- **"Is the DCP report accurate?"** It's computed live from the same
  inventory data — no manual tallying, so it can't drift from reality.

---

## 8. Glossary (DepEd terms presenters should know)

| Term | Meaning |
|---|---|
| **DepEd** | Department of Education (Philippines) |
| **SDO** | School Division Office — the org unit IEEPIS serves |
| **DCP** | DepEd Computerization Program — government-issued ICT packages |
| **PAR** | Property Acknowledgement Receipt — accountability form |
| **ICS** | Inventory Custodian Slip — accountability form (lower-value items) |
| **RRSP / RRPE** | Return/Reassignment property forms |
| **IAR / DR / OR / SI / WMR** | Inspection, Delivery, Official Receipt, Sales Invoice, Waste Material Report |
| **Accountable Officer** | Person who formally signed for the item on delivery |
| **Custodian / End-User** | Person currently using/holding the item (rotates) |
| **L4T / L4NT** | Laptops for Teaching / Laptops for Non-Teaching |
| **STV** | Smart TV package |
| **PSI Population** | Teacher/employee population used as the DCP coverage denominator |

---

*Generated as a presenter reference. For technical/architecture detail see
`ARCHITECTURE.md`, `.ai/decisions.md`, and `docs/`.*
