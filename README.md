# IEEPIS — ICT Equipment and Employee Profile Information System

**School Division Level ICT Inventory Management System**
Built with Laravel 11 + FilamentPHP v3 | DepEd Philippines

---

## 🏗️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend Framework | Laravel 11 |
| Admin Panel | FilamentPHP v3 |
| Database | MySQL 8.0+ |
| Authentication | Laravel Sanctum (via Filament) |
| Authorization | Spatie Laravel Permission |
| File Storage | Laravel Storage (local/S3-compatible) |
| Audit Logging | Spatie Activity Log |
| QR Code | SimpleSoftwareIO/simple-qrcode |
| PDF Export | barryvdh/laravel-dompdf |
| Excel Export | Maatwebsite/Laravel Excel |
| CSS | Tailwind CSS (Filament's built-in) |

---

## 📦 System Modules

### 1. 🏫 Schools / Stakeholder Profiles
- School name, code, district, governance level
- Geographic location with coordinates (View on Map)
- School head and admin staff contacts
- PSGC codes, GIDCA classification
- Remote school identification
- Relation managers: Personnel, Equipment, Documents, Tickets, Connectivity

### 2. 👤 Personnel Directory (Employee Profiles)
- Employee ID, complete name (with suffix support)
- Position, department, employment type (Teaching / Non-Teaching)
- OIC designation tracking
- Separation tracking (cause, date, transferred from/to)
- Non-DepEd funded personnel flag
- Equipment accountability view per employee

### 3. 🖥️ ICT Equipment Inventory (IEEPIS Core)
- Full IEEPIS fields: Property No., Old Property No., Serial Number
- Item type: Device Type / Equipment / Hardware / Software / Peripherals
- DCP / Non-DCP classification with package and year
- Category: High-Value (≥ ₱50,000) / Low-Value
- GL-SL Code and UACS Code for COA compliance
- Acquisition: cost, date, mode (Purchased/Donation/Grant), source
- Supporting documents: OR/SI/DR/IAR/RRSP at acquisition
- Warranty tracking with expiry alerts
- Condition (Good/Fair/Poor/Unserviceable), functional status
- Accountability status: Normal / Transferred / Stolen / Lost / Damaged / For Disposal
- Auto-generated QR Code for equipment tagging
- Full assignment history (PAR/ICS/RRSP/RRPE/WMR issuance tracking)

### 4. 🔗 Equipment Assignments
- Accountable Officer + Custodian/End User (separate)
- Assignment and receipt dates
- Transaction type: Beginning Inventory / Issuance / Transfer / Return
- Document reference (PAR No., ICS No., etc.)
- Full assignment history per equipment (audit trail)
- Only ONE active assignment at a time (enforced)

### 5. 📄 Documents & Receipts
- PAR, ICS, IAR, DR, OR, SI, WMR, RRSP, RRPE support
- Linked to school, equipment, and/or employee
- PDF and image file upload
- Stored in `storage/app/public/schools/{school_id}/documents/`
- Download directly from admin panel

### 6. 🌐 Internet Connectivity
- ISP account details (ISP, plan, account number)
- Contracted vs. actual speed (download/upload)
- Latency tracking
- Speed test results with date
- Connection type: Fiber/DSL/Wireless/LTE/Satellite
- Subscription dates and monthly cost

### 7. 🎫 Support Tickets
- Auto-generated ticket numbers (TKT-YYYY-XXXX)
- Priority: Low / Medium / High / Critical
- Status: Open / In-Progress / Pending / Resolved / Closed
- Linked to school, equipment, reporter, and assigned technician
- Resolution notes and timestamps

### 8. 📊 Dashboard
- Real-time stats: total equipment, assigned, unassigned, non-functional
- Warranty expiring alerts (within 90 days)
- Equipment by school (bar chart)
- Equipment condition distribution (doughnut chart)
- Open tickets table widget
- Global search (Ctrl+K / Cmd+K)

---

## 🚀 Installation Guide

### Prerequisites
- PHP 8.2+
- Composer 2.x
- MySQL 8.0+ or MariaDB 10.4+
- Node.js 18+ and NPM (for asset building)

### Step 1: Clone / Extract Project
```bash
unzip ieepis.zip -d ieepis
cd ieepis
```

### Step 2: Install PHP Dependencies
```bash
composer install
```

### Step 3: Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```
APP_NAME=IEEPIS
APP_URL=http://your-domain.com
DB_DATABASE=ieepis_db
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Optional: Configure mail for notifications
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your_password
```

### Step 4: Database Setup
```bash
# Create the database first in MySQL
mysql -u root -p -e "CREATE DATABASE ieepis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Seed with sample data
php artisan db:seed
```

### Step 5: Storage Link
```bash
php artisan storage:link
```

### Step 6: Create Admin User
```bash
php artisan make:filament-user
```

Or use the seeded default:
- **Email:** `admin@deped.gov.ph`
- **Password:** `P@ssw0rd123`

> ⚠️ Change the default password immediately in production!

### Step 7: (Optional) Build Assets
Filament uses pre-compiled assets, but if needed:
```bash
npm install && npm run build
```

### Step 8: Launch
```bash
php artisan serve
```

Visit: `http://localhost:8000/admin`

---

## 🗄️ Database Schema Overview

```
schools
  └── employees
  └── equipment
        └── equipment_assignments  ← accountability trail
        └── documents
        └── tickets
  └── documents
  └── tickets
  └── internet_connections
```

### Key Design Decisions
- **No `employee_id` on equipment** — accountability is tracked via `equipment_assignments` table only
- **Soft deletes** — schools, employees, equipment, documents, tickets are soft-deleted (recoverable)
- **Activity log** — all changes to schools, employees, and equipment are audited via Spatie ActivityLog
- **QR Code** — auto-generated on equipment creation, encodes property_no + serial_no + brand/model
- **Ticket numbers** — auto-incremented per year (TKT-2024-0001)

---

## 👥 User Roles (Spatie Permission)

| Role | Access |
|------|--------|
| Super Admin | Full access to all modules |
| SDO Admin | All schools in the division |
| School Admin | Own school only |
| Technician | Tickets and equipment status updates |
| Viewer | Read-only access |

---

## 🔧 Artisan Commands

```bash
# Run all migrations fresh with seed
php artisan migrate:fresh --seed

# Clear all caches
php artisan optimize:clear

# Generate QR codes for all equipment
php artisan ieepis:generate-qrcodes

# Export inventory to Excel
php artisan ieepis:export-inventory

# Check expiring warranties
php artisan ieepis:check-warranties
```

---

## 📁 File Storage Structure
```
storage/app/public/
  schools/
    logos/           ← School logo images
    {school_id}/
      documents/     ← PAR, ICS, IAR, etc.
  employees/
    photos/          ← Employee profile photos
  documents/         ← General documents
```

---

## 📝 IEEPIS Field Reference

Based on the official IEEPIS data dictionary:

| Field | Description |
|-------|-------------|
| Property No. | Official property number (coordinate with Asset Management) |
| Old Property No. | Previous property number (for bundled items split into components) |
| Serial Number | Manufacturer serial number |
| Item Type | Device Type / Equipment / Hardware / Software / Peripherals |
| DCP Package | DepEd Computerization Program batch name |
| GL-SL Code | Subsidiary Ledger Chart of Accounts code |
| UACS Code | Unified Accounts Code Structure |
| Category | High-Value (≥₱50,000) or Low-Value (<₱50,000) |
| Mode of Acquisition | Purchased / Donation / Grant |
| Supporting Docs (Acquisition) | OR / SI / DR / IAR / RRSP |
| Supporting Docs (Issuance) | PAR / ICS / RRSP / RRPE / WMR |
| Transaction Type | Beginning Inventory / Issuance / Transfer / Return / Disposal |
| QR Code | Auto-generated, for equipment tagging and quick inventory |

---

## 🆘 Support

For issues or customization:
- GitHub Issues: [your-repo-url]
- Email: ict@deped.gov.ph

---

*IEEPIS — Developed for DepEd School Division ICT Management*
*Laravel 11 + FilamentPHP v3 | © 2024 DepEd ICT Unit*
# ieepis
