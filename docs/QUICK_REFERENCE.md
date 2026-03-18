# IEEPIS Quick Reference Guide

## Overview
This guide provides a quick reference for the three main features implemented in IEEPIS:
1. School Admin Data Scoping
2. Helpdesk Ticket Notifications
3. Role-Based Access Control

---

## 1. School Admin Data Scoping

### What It Does
School Administrators can only view and manage data belonging to their assigned school.

### How It Works
- Each user has a `school_id` field that links them to a specific school
- All Filament resources filter data based on the logged-in user's `school_id`
- Database queries automatically exclude data from other schools

### User Experience
**As a School Admin:**
- ✅ See equipment only from your school
- ✅ See documents only from your school
- ✅ See employees only from your school
- ✅ See tickets only from your school
- ❌ Cannot see other schools' data
- ❌ Cannot access other schools' resources via URL

### Affected Resources
- Equipment Inventory
- Documents & Receipts (PAR, ICS, IAR, etc.)
- Employees/Personnel
- Support Tickets
- Schools (School Admin sees only their own school)
- Users (School Admin sees only their school's users)

### Key Files
- `app/Models/User.php` - Added `school_id` and `school()` relationship
- `app/Filament/Resources/*/` - All resources have `getEloquentQuery()` scoping method
- `database/migrations/*_add_school_id_to_users_table.php` - Database migration

---

## 2. Helpdesk Ticket Notifications

### What It Does
When a school creates a support ticket, all support staff (Technicians, SDO Admins, Super Admins) receive real-time notifications in the notification bell icon, sorted by priority.

### How It Works
1. School creates a new support ticket
2. `TicketObserver` detects the creation event
3. Notification is sent to all support staff users
4. Notification appears in the notification bell icon with priority color
5. Support staff clicks notification to navigate directly to the ticket

### Notification Priority Levels

| Priority | Color | Order | Usage |
|----------|-------|-------|-------|
| Critical | 🔴 Red | 1st | System down, data loss risk |
| High | 🟠 Orange | 2nd | Major functionality broken |
| Medium | 🔵 Blue | 3rd | Minor functionality broken |
| Low | ⚫ Gray | 4th | General inquiry, enhancement request |

### Notification Recipients
- Super Admins
- SDO Admins
- Technicians

### Notification Actions
- **View Button** - Click to navigate to ticket edit page
- **Dismiss** - Remove notification from bell
- **Quick Details** - See ticket number, priority, issue title, and school

### Key Files
- `app/Observers/TicketObserver.php` - Sends notifications on create/update
- `app/Providers/AppServiceProvider.php` - Registers observer
- `app/Models/Ticket.php` - Ticket model with events

### Example Notification Format
```
Title: New Support Ticket: TKT-2024-0001
Priority: CRITICAL (shown in red)
Issue: Internet connection unstable
School: Sample High School
[View] Button
```

---

## 3. Role-Based Access Control

### Roles in the System

```
┌─────────────────────────────────────────────────────────┐
│ Super Admin                                             │
│ • Full system access                                    │
│ • Manage all schools, users, equipment, tickets        │
│ • System configuration                                  │
└─────────────────────────────────────────────────────────┘
                         │
        ┌────────────────┼────────────────┐
        │                │                │
┌───────▼──────┐  ┌──────▼──────┐  ┌─────▼─────────┐
│ SDO Admin    │  │Technician   │  │ School Admin   │
│              │  │             │  │                │
│• All schools │  │• View tickets│  │• Own school   │
│  in division │  │• Resolve    │  │  only         │
│• User mgmt   │  │  tickets    │  │• Equipment    │
│• Tickets     │  │• Read-only  │  │• Documents    │
│              │  │  equipment  │  │• Employees    │
└──────────────┘  └─────────────┘  │• Tickets      │
                                    └────────────────┘
                         │
                ┌────────┴────────┐
                │                 │
         ┌──────▼──────┐   ┌──────▼──────┐
         │ Viewer      │   │ (Custom)    │
         │             │   │             │
         │• Read-only  │   │• Custom     │
         │  access to  │   │  permissions│
         │  resources  │   │             │
         └─────────────┘   └─────────────┘
```

### Permission Matrix

| Resource | Super Admin | SDO Admin | School Admin | Technician | Viewer |
|----------|:-----------:|:---------:|:------------:|:----------:|:------:|
| Equipment | CRUD | CRUD | CRUD* | Read | Read |
| Documents | CRUD | CRUD | CRUD* | Read | Read |
| Employees | CRUD | CRUD | CRUD* | Read | Read |
| Tickets | CRUD | CRUD | CRUD* | Update | Read |
| Users | CRUD | CRUD | Self | - | - |
| Schools | CRUD | Own | Own | - | - |

*CRUD = Own school only

### How Authorization Works

**Example: School Admin trying to view Equipment**
```
1. School Admin navigates to Equipment
2. Filament calls getEloquentQuery()
3. Query adds WHERE school_id = {user's school_id}
4. Database returns only matching records
5. School Admin sees only their school's equipment
```

**Example: School Admin trying to manage Users**
```
1. School Admin clicks Users
2. can() method checks: user->hasRole('school-admin') && action === 'edit'
3. Check: can they edit self? ($record->id === $user->id)
4. If YES: Allow, If NO: Deny access
```

### Assigning Roles to Users

**Via Artisan Tinker:**
```php
docker exec ieepis-app php artisan tinker

> $user = User::find(1);
> $user->assignRole('school-admin');
> $user->syncRoles(['school-admin']);  // Replace all roles
```

**Via Filament UI:**
1. Go to Users resource
2. Click Edit on a user
3. Select role(s) from the "Roles" multi-select
4. Save

---

## 4. Database Schema Changes

### Users Table Migration
```sql
ALTER TABLE users ADD school_id BIGINT UNSIGNED NULL;
ALTER TABLE users ADD FOREIGN KEY (school_id) 
  REFERENCES schools(id) ON DELETE SET NULL;
```

### New Columns
- `school_id` - Foreign key to schools table
- Nullable to allow super-admin/global users

---

## 5. Common Tasks

### Task: Create a School Admin User

**Step 1: Create User**
```php
$user = User::create([
    'name' => 'John School Admin',
    'email' => 'admin@school.example.com',
    'password' => Hash::make('password123'),
    'school_id' => 1,  // School ID
]);
```

**Step 2: Assign Role**
```php
$user->assignRole('school-admin');
```

**Step 3: Login and Verify**
- User logs in
- Should only see School ID 1's data
- Cannot access other schools' resources

### Task: Create a Support Ticket and Receive Notification

**Step 1: School Admin creates ticket**
- Logs into Filament
- Navigate to Tickets → Create
- Fill in:
  - School: Select their school
  - Issue Title: "Internet not working"
  - Description: Detailed description
  - Priority: "High"
  - Reporter: Select employee

**Step 2: Notification sent**
- Auto-generated ticket number: TKT-2024-0001
- Notification sent to all support staff
- Appears in notification bell with HIGH priority (orange)

**Step 3: Technician views notification**
- Technician logs in
- Sees notification in bell icon
- Clicks notification
- Redirected to ticket edit page
- Can update status to "In Progress" and add resolution notes

### Task: Filter Equipment by School

**As Super Admin:**
1. Go to Equipment resource
2. Use filter: School = "Sample School"
3. See all equipment for that school

**As School Admin:**
1. Go to Equipment resource
2. Automatically filtered to your school
3. No filter option needed (already scoped)

---

## 6. Troubleshooting

### Problem: School Admin sees data from other schools

**Cause:** User doesn't have correct `school_id` in database

**Solution:**
```php
docker exec ieepis-app php artisan tinker
> User::find(1)->update(['school_id' => 1]);
> exit
```

### Problem: Notifications not appearing

**Cause 1:** Observer not registered
**Solution:** Check `AppServiceProvider.php` has `Ticket::observe(TicketObserver::class);`

**Cause 2:** User doesn't have correct role
**Solution:** Verify user has super-admin, sdo-admin, or technician role

**Cause 3:** Cache issue
**Solution:**
```bash
docker exec ieepis-app php artisan cache:clear
docker exec ieepis-app php artisan config:clear
```

### Problem: User cannot access any resource

**Cause:** User doesn't have role assigned

**Solution:**
```php
docker exec ieepis-app php artisan tinker
> $user = User::find(1);
> $user->assignRole('school-admin');
```

---

## 7. Testing Checklist

- [ ] Create a School Admin user for School A
- [ ] Login as School Admin
- [ ] Verify can see School A equipment
- [ ] Verify cannot see School B equipment
- [ ] Create a support ticket with "Critical" priority
- [ ] Switch to Super Admin account
- [ ] Check notification bell for the ticket
- [ ] Click notification and verify it opens the ticket
- [ ] Verify notification has RED color for Critical priority
- [ ] Create ticket with "Low" priority
- [ ] Verify notification appears with GRAY color
- [ ] Test editing School Admin profile (should work)
- [ ] Test editing another School Admin profile (should be denied)

---

## 8. Quick Commands

```bash
# Run migrations
docker exec ieepis-app php artisan migrate

# Clear caches
docker exec ieepis-app php artisan optimize:clear

# Open Tinker shell
docker exec ieepis-app php artisan tinker

# Check app loads
docker exec ieepis-app php artisan config:clear

# Fix file permissions
docker exec ieepis-app chown -R www-data:www-data /var/www/app
docker exec ieepis-app chmod -R 777 /var/www/app

# Access Filament admin panel
http://localhost:8080/admin
```

---

## 9. File Locations Reference

```
ieepis/
├── app/
│   ├── Models/
│   │   └── User.php                          ← User model with school_id
│   ├── Filament/Resources/
│   │   ├── UserResource.php                  ← User management resource
│   │   ├── EquipmentResource.php             ← Equipment with scoping
│   │   ├── DocumentResource.php              ← Documents with scoping
│   │   ├── TicketResource.php                ← Tickets with scoping
│   │   ├── EmployeeResource.php              ← Employees with scoping
│   │   └── SchoolResource.php                ← Schools with scoping
│   ├── Observers/
│   │   └── TicketObserver.php                ← Ticket notifications
│   └── Providers/
│       └── AppServiceProvider.php            ← Observer registration
├── database/
│   └── migrations/
│       └── *_add_school_id_to_users_table.php ← Migration
└── docs/
    ├── IMPLEMENTATION.md                     ← Full documentation
    └── QUICK_REFERENCE.md                    ← This file
```

---

## 10. Support Contact

For issues or questions:
- **Email:** ict@deped.gov.ph
- **Documentation:** See `docs/IMPLEMENTATION.md`
- **Issues:** GitHub Issues (if available)

---

**Last Updated:** March 17, 2024  
**Version:** 1.0  
**Status:** Production Ready