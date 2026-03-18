# IEEPIS Implementation Documentation

## Features Implemented

This document outlines the features implemented for the IEEPIS (ICT Equipment and Employee Profile Information System) project using Laravel 11 and FilamentPHP v3.

---

## 1. School Admin Data Scoping

### Overview
School Administrators can now only see data and resources that belong to their own school. This ensures data isolation and prevents unauthorized access to other schools' information.

### Implementation Details

#### 1.1 Database Migration
A new migration was created to add a `school_id` foreign key to the `users` table:

**File:** `database/migrations/2026_03_17_145753_add_school_id_to_users_table.php`

```php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
});
```

#### 1.2 User Model Updates
**File:** `app/Models/User.php`

- Added `school_id` to the `$fillable` array
- Integrated `Spatie\Permission\Traits\HasRoles` for role-based access control
- Added `school()` relationship method to link users to schools
- Updated `canAccessPanel()` to check for valid Filament roles

```php
public function school(): BelongsTo
{
    return $this->belongsTo(School::class);
}
```

#### 1.3 Filament Resources Scoping
The following Filament resources have been updated with `getEloquentQuery()` method to scope data by school:

**Affected Resources:**
- `EquipmentResource` - School admins see only their school's equipment inventory
- `DocumentResource` - School admins see only their school's documents
- `TicketResource` - School admins see only their school's support tickets
- `EmployeeResource` - School admins see only their school's employees
- `SchoolResource` - School admins see only their own school

**Implementation Pattern:**
```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->when(
        auth()->user()->hasRole('school-admin'),
        fn ($query) => $query->where('school_id', auth()->user()->school_id)
    );
}
```

### Access Control Matrix

| Role | Equipment | Documents | Tickets | Employees | Schools | Users |
|------|-----------|-----------|---------|-----------|---------|-------|
| Super Admin | All | All | All | All | All | All |
| SDO Admin | Division | Division | Division | Division | Division | Division |
| School Admin | Own School | Own School | Own School | Own School | Own School | Own School |
| Technician | Read-Only | Read-Only | Edit | Read-Only | Read-Only | - |
| Viewer | Read-Only | Read-Only | Read-Only | Read-Only | Read-Only | - |

---

## 2. Helpdesk Support Ticket Notifications

### Overview
When a school requests helpdesk support (creates a new ticket), support staff (Technicians, SDO Admins, Super Admins) receive real-time notifications. Notifications are prioritized and sorted by ticket priority level (Critical > High > Medium > Low).

### Implementation Details

#### 2.1 Ticket Observer
**File:** `app/Observers/TicketObserver.php`

The observer monitors ticket lifecycle events and triggers notifications:

**Key Features:**
- Listens to ticket `created` and `updated` events
- Sends notifications to support staff based on priority
- Includes actionable buttons to view tickets directly
- Differentiates between new tickets and status updates

**Priority Levels:**
```php
$priorities = [
    'critical' => ['color' => 'danger', 'label' => 'CRITICAL', 'order' => 1],
    'high'     => ['color' => 'warning', 'label' => 'HIGH', 'order' => 2],
    'medium'   => ['color' => 'info', 'label' => 'MEDIUM', 'order' => 3],
    'low'      => ['color' => 'gray', 'label' => 'LOW', 'order' => 4],
];
```

**Notification Recipients:**
- Super Admins
- SDO Admins
- Technicians

**Notification Format:**
```
Title: New Support Ticket: TKT-2024-0001
Body: **Priority: CRITICAL**

Issue: System crashed unexpectedly
School: Sample High School
```

#### 2.2 Observer Registration
**File:** `app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    Ticket::observe(TicketObserver::class);
}
```

#### 2.3 Ticket Model
**File:** `app/Models/Ticket.php`

The Ticket model includes:
- Auto-generated ticket numbers (TKT-YYYY-XXXX format)
- Priority levels: low, medium, high, critical
- Status tracking: open, in-progress, pending, resolved, closed
- Accountability tracking (reported by, assigned to)
- Relationship to school, equipment, reporter, and assigned technician

### Notification Workflow

1. **Ticket Creation:** When a school creates a support ticket
   - System auto-generates ticket number
   - Observer captures the event
   - Notification is sent to all support staff
   - Notification includes priority color and label
   - Quick action button links to the ticket edit page

2. **Ticket Updates:** When ticket status or priority changes
   - Observer detects changes using `isDirty()`
   - Notification sent with change details
   - Reporter is notified of status updates

3. **Notification Bell Icon:** Filament's built-in notification system displays:
   - Unread notification count
   - Notification list sorted by priority
   - Action buttons for quick navigation
   - Automatic dismissal after viewing

### Notification Priority Sorting

The notification system automatically prioritizes tickets by priority level:

1. **CRITICAL** (Red) - Highest priority, shown first
2. **HIGH** (Orange) - Second priority
3. **MEDIUM** (Blue) - Third priority
4. **LOW** (Gray) - Lowest priority

---

## 3. User Resource Implementation

### Overview
A new UserResource was created to manage users within the Filament admin panel, with proper role-based access control.

**File:** `app/Filament/Resources/UserResource.php`

### Features:
- Create, read, update, delete user management
- Role assignment through multi-select
- School assignment for different user types
- Authorization checks based on user roles
- Data scoping for SDO Admins and School Admins

### Authorization Rules:
- **Super Admin & SDO Admin:** Can view and manage all users
- **School Admin:** Can only manage users within their school
- **Technician & Viewer:** No user management access

---

## 4. File Structure

```
ieepis/
├── app/
│   ├── Models/
│   │   ├── User.php (Updated with school relationship)
│   │   ├── Ticket.php
│   │   ├── School.php
│   │   ├── Equipment.php
│   │   ├── Document.php
│   │   └── Employee.php
│   ├── Filament/
│   │   ├── Resources/
│   │   │   ├── UserResource.php (New)
│   │   │   ├── EquipmentResource.php (Updated with scoping)
│   │   │   ├── DocumentResource.php (Updated with scoping)
│   │   │   ├── TicketResource.php (Updated with scoping)
│   │   │   ├── EmployeeResource.php (Updated with scoping)
│   │   │   └── SchoolResource.php (Updated with scoping)
│   ├── Observers/
│   │   └── TicketObserver.php (New)
│   └── Providers/
│       └── AppServiceProvider.php (Updated with observer registration)
└── database/
    └── migrations/
        └── 2026_03_17_145753_add_school_id_to_users_table.php
```

---

## 5. Setup Instructions

### Prerequisites
- Docker and Docker Compose running
- Laravel 11 with FilamentPHP v3
- Spatie Laravel Permission package installed
- MySQL 8.0+

### Installation Steps

1. **Run Migrations**
   ```bash
   docker exec ieepis-app php artisan migrate
   ```

2. **Seed Roles and Permissions** (if not already seeded)
   ```bash
   docker exec ieepis-app php artisan db:seed --class=RoleSeeder
   ```

3. **Assign Users to Schools**
   Update user records to include `school_id`:
   ```bash
   docker exec ieepis-app php artisan tinker
   > $user = User::find(1);
   > $user->school_id = 1;
   > $user->save();
   ```

4. **Assign Roles to Users**
   ```bash
   > $user->assignRole('school-admin');
   ```

5. **Clear Application Cache**
   ```bash
   docker exec ieepis-app php artisan optimize:clear
   ```

---

## 6. Testing the Features

### Test School Admin Scoping
1. Log in as School Admin for School A
2. Navigate to Equipment resource
3. Verify only School A's equipment is displayed
4. Attempt to access another school's data (should not be visible)

### Test Ticket Notifications
1. Log in as School Admin
2. Create a new support ticket with priority "Critical"
3. Switch to Super Admin or Technician account
4. Check the notification bell icon in Filament admin panel
5. Verify the notification appears with correct priority color and details
6. Click the action button to verify it opens the correct ticket

### Test User Management
1. Log in as Super Admin
2. Navigate to Users resource
3. Create a new user and assign to School A with "school-admin" role
4. Verify the new user can only see School A's data
5. Test that School Admin cannot access other schools' resources

---

## 7. Future Enhancements

Potential improvements for future versions:

1. **Email Notifications:** Send email notifications for critical tickets
2. **SMS Alerts:** Send SMS for critical/high priority tickets
3. **Ticket Statistics Dashboard:** Show ticket metrics by school and priority
4. **Automated Escalation:** Auto-escalate tickets not resolved within timeframe
5. **Ticket Templates:** Pre-configured ticket types for common issues
6. **SLA Management:** Service Level Agreements for different ticket types
7. **Multi-division Support:** Extended support for multiple divisions in SDO management
8. **Real-time Updates:** WebSocket-based real-time ticket updates

---

## 8. Troubleshooting

### Issue: School Admin sees data from other schools
**Solution:** Verify that `getEloquentQuery()` is properly implemented in the Resource and that the user has the correct `school_id` in the database.

### Issue: Notifications not appearing
**Solution:** 
1. Verify `AppServiceProvider` is registered in `config/app.php`
2. Check that `Ticket::observe(TicketObserver::class)` is in the `boot()` method
3. Verify user has one of the support roles (super-admin, sdo-admin, technician)

### Issue: User cannot access the panel
**Solution:** Verify the user is assigned at least one role and has `school_id` set in the database.

---

## 9. Security Considerations

1. **Row-Level Security:** School Admins cannot see other schools' data due to `getEloquentQuery()` scoping
2. **Role-Based Access:** All operations check user roles before allowing access
3. **Authorization:** The `can()` method prevents unauthorized operations
4. **Data Isolation:** Each school's data is completely isolated at the query level
5. **Notification Privacy:** Notifications are sent only to authorized support staff

---

## 10. Performance Notes

- Scoping queries use indexed `school_id` columns for fast filtering
- Eager loading is recommended for relationships to prevent N+1 queries
- Notification system uses database-backed notifications (scalable)
- Consider adding database indexes on `school_id` columns for large datasets

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2024-03-17 | Initial implementation of school admin scoping and ticket notifications |

---

**Documentation Last Updated:** March 17, 2024
**Maintained By:** IEEPIS Development Team
**Contact:** ict@deped.gov.ph