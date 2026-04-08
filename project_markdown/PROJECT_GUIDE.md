# IEEPIS Project Guide

## What is IEEPIS?

IEEPIS (ICT Equipment and Employee Profile Information System) is a school division-level ICT inventory management system built for the Department of Education (DepEd) Philippines using Laravel 11 and FilamentPHP v3.

Think of it like a digital filing cabinet for schools - it keeps track of:
- All ICT equipment (computers, printers, etc.)
- Employee information
- School details
- Support tickets
- Documents and receipts
- Internet connectivity

## Technology Stack

| Component | Technology |
|-----------|------------|
| Backend | Laravel 11 |
| Admin Panel | FilamentPHP v3 |
| Database | MySQL 8.0+ |
| Authorization | Spatie Laravel Permission |
| Styling | Tailwind CSS |
| QR Codes | SimpleSoftwareIO/simple-qrcode |
| PDF Export | barryvdh/laravel-dompdf |
| Excel Export | Maatwebsite/Laravel Excel |

## Core Features

### 1. Schools Management
- School name, code, district, governance level
- Geographic location with coordinates
- School head and admin staff contacts
- PSGC codes, GIDCA classification

### 2. Personnel Directory
- Employee ID, complete name (with suffix support)
- Position, department, employment type
- OIC designation tracking
- Separation tracking

### 3. ICT Equipment Inventory
- Property No., Old Property No., Serial Number
- Item type: Device/Equipment/Hardware/Software/Peripherals
- DCP/Non-DCP classification
- Category: High-Value (>=50,000 PHP) / Low-Value
- GL-SL Code and UACS Code for COA compliance
- Acquisition details (cost, date, mode)
- Warranty tracking with expiry alerts
- Condition tracking (Good/Fair/Poor/Unserviceable)
- Auto-generated QR Code

### 4. Equipment Assignments
- Accountable Officer + Custodian/End User
- Assignment and receipt dates
- Transaction type: Beginning Inventory/Issuance/Transfer/Return
- Full assignment history

### 5. Documents and Receipts
- PAR, ICS, IAR, DR, OR, SI, WMR, RRSP, RRPE support
- PDF and image file upload
- Download from admin panel

### 6. Internet Connectivity
- ISP account details
- Speed tracking (contracted vs actual)
- Latency tracking
- Connection type: Fiber/DSL/Wireless/LTE/Satellite

### 7. Support Tickets
- Auto-generated ticket numbers (TKT-YYYY-XXXX)
- Priority: Low/Medium/High/Critical
- Status: Open/In-Progress/Pending/Resolved/Closed
- Linked to school, equipment, reporter, technician

### 8. Dashboard
- Real-time stats (total equipment, assigned, unassigned)
- Warranty expiring alerts (within 90 days)
- Equipment by school (bar chart)
- Equipment condition distribution (doughnut chart)
- Open tickets table
- Global search (Ctrl+K / Cmd+K)

## User Roles

| Role | Access |
|------|--------|
| Super Admin | Full access to all modules |
| SDO Admin | All schools in the division |
| School Admin | Own school only |
| Technician | Tickets and equipment status updates |
| Viewer | Read-only access |

## Project Structure

```
ieepis/
├── app/
│   ├── Models/                 # Database models (School, Equipment, Employee, etc.)
│   ├── Filament/               # Filament admin panel
│   │   ├── Resources/          # Resource classes (CRUD pages)
│   │   └── Pages/              # Custom pages (Dashboard, etc.)
│   ├── Observers/              # Model observers (TicketObserver.php)
│   └── Providers/              # Service providers
├── database/
│   ├── migrations/             # Database tables
│   └── seeders/                # Sample data
├── routes/
│   └── web.php                 # Web routes
└── storage/                    # File storage
```

## How to Add/Edit Features

### Adding a New Database Table

1. Create a migration:
```bash
php artisan make:migration create_newtable_table
```

2. Edit the migration file in `database/migrations/`

3. Run the migration:
```bash
php artisan migrate
```

4. Create a model:
```bash
php artisan make:model NewTable
```

### Adding a New Filament Resource (Admin Page)

1. Generate the resource:
```bash
php artisan make:filament-resource NewTable --generate
```

2. This creates:
   - `app/Filament/Resources/NewTableResource.php`
   - `app/Filament/Resources/NewTableResource/Pages/`

3. Customize the resource by editing the generated files

### Example: Adding a New Field to Equipment

1. Create a migration:
```bash
php artisan make:migration add_new_field_to_equipment_table
```

2. Edit the migration:
```php
$table->string('new_field')->nullable();
```

3. Run migration:
```bash
php artisan migrate
```

4. Update the model (`app/Models/Equipment.php`):
```php
protected $fillable = [..., 'new_field'];
```

5. Update the Filament resource form:
```php
Forms\\Components\\TextInput::make('new_field')
    ->label('New Field')
    ->nullable(),
```

### Modifying the Dashboard

1. Find the dashboard file: `app/Filament/Pages/DcpDashboard.php`

2. Modify the `getWidgets()` method to add new widgets

3. Or create a new widget:
```bash
php artisan make:filament-widget StatsOverview --stats-overview
```

### Changing User Roles/Permissions

1. Find the seeder: `database/seeders/RoleSeeder.php`

2. Modify the roles array

3. Re-seed:
```bash
php artisan db:seed --class=RoleSeeder
```

### Adding a Notification

1. Create a notification:
```bash
php artisan make:notification NewNotification
```

2. Edit the notification class in `app/Notifications/`

3. Send it from your controller or observer:
```php
Notification::send($user, new NewNotification($data));
```

## Common Tasks

### Clear Cache
```bash
php artisan optimize:clear
```

### Create Admin User
```bash
php artisan make:filament-user
```

### Generate QR Codes
```bash
php artisan ieepis:generate-qrcodes
```

### Export Inventory to Excel
```bash
php artisan ieepis:export-inventory
```

### Check Expiring Warranties
```bash
php artisan ieepis:check-warranties
```

## Key Files to Know

| File | Purpose |
|------|---------|
| `app/Models/School.php` | School data model |
| `app/Models/Equipment.php` | Equipment data model |
| `app/Models/Employee.php` | Employee data model |
| `app/Models/Ticket.php` | Support ticket model |
| `app/Filament/Resources/EquipmentResource.php` | Equipment admin page |
| `app/Filament/Resources/TicketResource.php` | Ticket admin page |
| `app/Observers/TicketObserver.php` | Ticket event handling |
| `database/migrations/` | Database table definitions |
| `routes/web.php` | Web routes |

## Understanding the Flow

### How Data Gets Saved (Example: Creating a Ticket)

1. **User fills form** in Filament admin panel
2. **Livewire** sends the data to the server (no page reload)
3. **Controller** receives the data
4. **Model** saves it to the database
5. **Observer** detects the new ticket and sends notifications
6. **UI updates** automatically via Livewire

### How Permissions Work

1. User logs in
2. Filament checks user's roles (from Spatie Permission)
3. `getEloquentQuery()` scopes data based on role
4. School Admin only sees their school's data
5. SDO Admin sees all schools in their division
6. Super Admin sees everything

## Tips for Beginners

1. **Start small** - Make small changes and test frequently
2. **Use `php artisan tinker`** - Great for testing database queries
3. **Check the logs** - `storage/logs/laravel.log`
4. **Use `dd()`** - Dump data to debug (but remove in production)
5. **Read the Filament docs** - https://filamentphp.com/docs

## Troubleshooting

### Page not loading
- Clear cache: `php artisan optimize:clear`
- Check .env database settings

### Permission errors
- Re-seed roles: `php artisan db:seed --class=RoleSeeder`
- Check user has correct role in database

### Not seeing data
- Check `school_id` is set on user
- Verify `getEloquentQuery()` is implemented

---

*Created for learning purposes - IEEPIS Laravel + Filament Project*
