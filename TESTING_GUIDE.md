# IEEPIS Testing Guide

## Quick Start Testing

### Prerequisites
- Docker and Docker Compose installed
- Project running with `docker compose up -d`
- Database migrated and seeded

### Login Credentials

#### Super Admin (Full Access)
```
Email:    admin@deped.gov.ph
Password: P@ssw0rd123
Role:     super-admin
School:   None (can access all schools)
```

---

## Test Scenario 1: Super Admin Access

### Objective
Verify that super-admin users can see all schools' data without restrictions.

### Steps

1. **Login as Super Admin**
   ```
   URL: http://localhost:8080/admin
   Email: admin@deped.gov.ph
   Password: P@ssw0rd123
   ```

2. **Navigate to Assignments**
   - Click "Assignments" in the sidebar
   - Expected: Should see 8 assignments from all 4 schools

3. **Verify Assignment Details**
   - Click on any assignment
   - Expected: Should see "School" field with value
   - School field should be ENABLED (editable)

4. **Filter by School**
   - Look for "School" filter in table filters
   - Expected: School filter should be VISIBLE to super-admin
   - Select a school from dropdown
   - Expected: Table should filter to show only that school's assignments

5. **Equipment Resource**
   - Navigate to Equipment
   - Expected: Should see all 9 equipment items from all 4 schools

6. **Verify No Restrictions**
   - Navigate to Employees
   - Expected: Should see all 8 employees from all schools
   - Navigate to Documents
   - Expected: Should see all 5 documents from all schools

### Expected Results
- ✅ Can view all records from all schools
- ✅ No filters applied automatically
- ✅ Can create assignments for any school
- ✅ School field is editable in forms

---

## Test Scenario 2: School Admin Access (Create Test User First)

### Objective
Verify that school-admin users can only see their assigned school's data.

### Steps to Create Test User

1. **Open Laravel Tinker**
   ```bash
   docker compose exec app php artisan tinker
   ```

2. **Create School Admin for School 1**
   ```php
   $user = \App\Models\User::create([
       'name' => 'School 1 Principal',
       'email' => 'school1admin@deped.gov.ph',
       'password' => bcrypt('password123'),
       'school_id' => 1  // Davao City National High School
   ]);
   $user->assignRole('school-admin');
   echo "User created successfully!";
   exit;
   ```

3. **Create School Admin for School 2**
   ```php
   $user = \App\Models\User::create([
       'name' => 'School 2 Principal',
       'email' => 'school2admin@deped.gov.ph',
       'password' => bcrypt('password123'),
       'school_id' => 2  // Mintal National High School
   ]);
   $user->assignRole('school-admin');
   echo "User created successfully!";
   exit;
   ```

### Testing School 1 Admin

1. **Login as School 1 Admin**
   ```
   URL: http://localhost:8080/admin
   Email: school1admin@deped.gov.ph
   Password: password123
   ```

2. **Check Dashboard**
   - Navbar should show: "School 1 Principal | School Admin | Davao City National High School"
   - Expected: User info displayed correctly

3. **Navigate to Assignments**
   - Expected: Should see ONLY 3 assignments from School 1:
     * HP EliteBook 840 G8 → Carlo Reyes
     * Dell Optiplex 3080 → Jenny Santos
     * Cisco Catalyst 2960 → Carlo Reyes
   - School filter should be HIDDEN
   - School field should be DISABLED in create/edit forms

4. **Try to View Other Schools**
   - Expected: Cannot see School 2, 3, or 4 assignments
   - Query should have WHERE clause filtering by school_id

5. **Check Equipment**
   - Navigate to Equipment
   - Expected: Should see ONLY 4 equipment items from School 1
   - Other schools' equipment should not be visible

6. **Check Employees**
   - Navigate to Employees
   - Expected: Should see ONLY 3 employees from School 1
   - Other schools' employees should not be visible

### Expected Results
- ✅ Can only view their school's records
- ✅ Cannot see other schools' data
- ✅ School field is hidden/disabled in forms
- ✅ Navbar shows correct school name
- ✅ All queries automatically filtered by school_id

---

## Test Scenario 3: Verify School Isolation

### Objective
Confirm that queries are properly scoped and no data leaks between schools.

### Setup
- Logged in as School 2 Admin (school2admin@deped.gov.ph)

### Steps

1. **Equipment Count**
   ```
   Expected for School 2: 2 equipment items
   - Lenovo IdeaPad 5
   - Epson L6170
   ```

2. **Employee Count**
   ```
   Expected for School 2: 2 employees
   - Mark Villanueva (Network Administrator)
   - Ana Cruz (Teacher II)
   ```

3. **Assignment Count**
   ```
   Expected for School 2: 2 assignments
   - Lenovo IdeaPad 5 → (For Disposal, not actively assigned)
   - Epson L6170 → Mark Villanueva (marked for disposal)
   ```

4. **Try Direct URL Access**
   ```
   Note: If you try to manually craft URLs to see other schools' data,
   the SchoolScope should intercept and return no results
   ```

### Expected Results
- ✅ Exact record counts match per school
- ✅ No data from other schools visible
- ✅ No error messages when filtered
- ✅ Proper audit trail in activity_log table

---

## Test Scenario 4: Role-Based Access Control

### Create Test Users for Each Role

#### Create SDO Admin
```bash
docker compose exec app php artisan tinker

$user = \App\Models\User::create([
    'name' => 'SDO Administrator',
    'email' => 'sdoadmin@deped.gov.ph',
    'password' => bcrypt('password123'),
    'school_id' => 1
]);
$user->assignRole('sdo-admin');
exit;
```

#### Create Technician
```bash
docker compose exec app php artisan tinker

$user = \App\Models\User::create([
    'name' => 'School Technician',
    'email' => 'technician@deped.gov.ph',
    'password' => bcrypt('password123'),
    'school_id' => 1
]);
$user->assignRole('technician');
exit;
```

### Test Each Role

| Role | Email | School | Expected Behavior |
|------|-------|--------|-------------------|
| super-admin | admin@deped.gov.ph | None | See all, manage all |
| sdo-admin | sdoadmin@deped.gov.ph | School 1 | See School 1 only, manage assignments |
| school-admin | school1admin@deped.gov.ph | School 1 | See School 1 only |
| technician | technician@deped.gov.ph | School 1 | See School 1 only, can create tickets |

### Test Permissions

1. **Super Admin Can:**
   - ✅ Access all resources
   - ✅ Edit/delete any record
   - ✅ Switch between schools
   - ✅ Create users

2. **SDO Admin Can:**
   - ✅ Access School 1 resources only
   - ✅ Create assignments
   - ✅ View equipment and employees
   - ❌ Cannot see other schools

3. **School Admin Can:**
   - ✅ View resources in their school
   - ✅ Create new assignments
   - ✅ Manage documents
   - ❌ Cannot access super-admin features

4. **Technician Can:**
   - ✅ View equipment in their school
   - ✅ Create support tickets
   - ✅ Report issues
   - ❌ Cannot manage other technicians

---

## Test Scenario 5: Navbar User Display

### Objective
Verify the navbar user widget displays correctly for all users.

### Test Cases

1. **Super Admin Navbar**
   ```
   Expected Display:
   System Administrator
   Super Admin
   (No school name - super-admin has no specific school)
   ```

2. **School Admin Navbar**
   ```
   Expected Display:
   School 1 Principal
   School Admin
   Davao City National High School
   ```

3. **Technician Navbar**
   ```
   Expected Display:
   School Technician
   Technician
   Davao City National High School
   ```

### Verification Steps

1. **Login as each role**
2. **Check top-right navbar area**
3. **Verify:**
   - ✅ User name is displayed
   - ✅ Role is formatted correctly (hyphens replaced with spaces)
   - ✅ School name appears (if user has school_id)
   - ✅ Layout is responsive (hidden on mobile, visible on desktop)

---

## Test Scenario 6: Global Query Scope Verification

### Objective
Confirm that SchoolScope is properly applied to all models.

### Test with Tinker

```bash
docker compose exec app php artisan tinker

# Test 1: EquipmentAssignment Scope
$assignments = \App\Models\EquipmentAssignment::all();
echo "Assignments (with scope): " . count($assignments);
# Expected if logged in as School 1 Admin: 3
# Expected if logged in as Super Admin: 8

# Test 2: Equipment Scope
$equipment = \App\Models\Equipment::all();
echo "Equipment (with scope): " . count($equipment);
# Expected if logged in as School 1 Admin: 4
# Expected if logged in as Super Admin: 9

# Test 3: Employee Scope
$employees = \App\Models\Employee::all();
echo "Employees (with scope): " . count($employees);
# Expected if logged in as School 1 Admin: 3
# Expected if logged in as Super Admin: 8

# Test 4: Document Scope
$documents = \App\Models\Document::all();
echo "Documents (with scope): " . count($documents);
# Expected if logged in as School 1 Admin: 2
# Expected if logged in as Super Admin: 5

# Test 5: Ticket Scope
$tickets = \App\Models\Ticket::all();
echo "Tickets (with scope): " . count($tickets);
# Expected if logged in as School 1 Admin: 2
# Expected if logged in as Super Admin: 4
```

### Expected Results
- ✅ Scope applied to all 5 models
- ✅ Counts match expected values per role
- ✅ No exceptions thrown
- ✅ Queries execute quickly

---

## Test Scenario 7: Create Assignment as Different Roles

### Objective
Verify that assignment creation respects school boundaries.

### Setup
- Login as School 1 Admin

### Steps

1. **Navigate to Assignments → Create**
   - Expected: School field shows "Davao City National High School" (pre-selected)
   - Expected: School field is DISABLED (cannot change)

2. **Select Equipment**
   - Only School 1 equipment should be available in dropdown
   - Expected: See 4 equipment items from School 1

3. **Select Employee**
   - Only School 1 employees should be available
   - Expected: See 3 employees from School 1

4. **Complete Form**
   ```
   Equipment: HP EliteBook 840 G8
   Employee: Carlo Reyes
   Transaction Type: Issuance
   Date Assigned: [Today]
   Assigned By: School 1 Admin
   ```

5. **Submit**
   - Expected: Assignment created successfully
   - Expected: New assignment visible in table
   - Expected: school_id automatically set to 1

### Test as Super Admin

1. **Navigate to Assignments → Create**
   - Expected: School field is ENABLED and shows dropdown
   - Expected: Can select ANY school

2. **Create Assignment for School 2**
   ```
   School: Mintal National High School
   Equipment: Lenovo IdeaPad 5 (School 2)
   Employee: Mark Villanueva (School 2)
   ```

3. **Expected Result**
   - ✅ Assignment created for School 2
   - ✅ Super admin can create cross-school assignments

---

## Test Scenario 8: Notifications & Tickets

### Objective
Verify ticket notifications work and respect school boundaries.

### Steps

1. **Login as School 1 Admin**

2. **Create Support Ticket**
   - Navigate to Tickets → Create
   - Fill in ticket details:
     ```
     Equipment: Cisco Catalyst 2960 (School 1)
     Issue Title: Network connectivity problem
     Description: Switch port 12 not responding
     Priority: High
     ```

3. **Submit Ticket**
   - Expected: Ticket created with school_id = 1
   - Expected: Notifications sent to:
     * Super admin
     * SDO admin (if assigned to School 1)
     * Technicians in School 1

4. **Check Notifications**
   - Look for notification bell icon in navbar
   - Expected: Notification appears for super-admin
   - Expected: Notification shows ticket details

5. **Login as School 2 Admin**
   - Expected: School 2 Admin cannot see School 1's ticket
   - Expected: No notification about School 1's ticket

### Expected Results
- ✅ Tickets respect school boundaries
- ✅ Notifications sent to correct roles
- ✅ No cross-school notification leakage

---

## Test Scenario 9: Audit Trail & Activity Log

### Objective
Verify that all actions are logged with proper school context.

### Steps

1. **Create an assignment**
   - As School 1 Admin

2. **Check Activity Log**
   ```bash
   docker compose exec app php artisan tinker
   
   $logs = \Spatie\Activitylog\Models\Activity::latest()->take(10)->get();
   foreach ($logs as $log) {
       echo $log->description . " - " . $log->model_type . "\n";
   }
   exit;
   ```

3. **Verify Details**
   - Expected: Log shows "created" action
   - Expected: Log includes school_id in properties
   - Expected: Only admin can see all logs

### Expected Results
- ✅ All changes are logged
- ✅ School context is maintained
- ✅ Audit trail is complete

---

## Database Testing Queries

### Verify School Scope in Database

```bash
docker compose exec app php artisan tinker

# Show all schools
\App\Models\School::all()->pluck('name', 'id');

# Show equipment by school
\App\Models\Equipment::select('id', 'school_id', 'brand', 'model')->get();

# Show assignments with school
\App\Models\EquipmentAssignment::with('school', 'equipment', 'employee')->get();

# Count by school
\App\Models\Equipment::groupBy('school_id')->selectRaw('school_id, count(*) as count')->get();

exit;
```

---

## Performance Testing

### Query Performance

```bash
docker compose exec app php artisan tinker

# Measure query time for assignments
$start = microtime(true);
$assignments = \App\Models\EquipmentAssignment::with('equipment', 'employee', 'school')->get();
$time = microtime(true) - $start;
echo "Time taken: " . round($time * 1000, 2) . "ms\n";
echo "Count: " . count($assignments) . "\n";

# Expected: < 100ms for ~8 records

exit;
```

### Expected Results
- ✅ Queries execute in < 100ms
- ✅ Scope doesn't add significant overhead
- ✅ Eager loading works properly

---

## Troubleshooting Test Failures

### Issue: "Cannot see any assignments"
**Diagnosis:** User might not have school_id set
**Fix:** 
```bash
docker compose exec app php artisan tinker
$user = \App\Models\User::find(2);
$user->update(['school_id' => 1]);
exit;
```

### Issue: "School filter is hidden but should be visible"
**Diagnosis:** User might not have super-admin role
**Fix:**
```bash
docker compose exec app php artisan tinker
$user = \App\Models\User::find(2);
$user->syncRoles('super-admin');
exit;
```

### Issue: "Cannot create assignment - no equipment available"
**Diagnosis:** Equipment might be in wrong school
**Fix:** Check equipment school_id matches user's school_id

### Issue: "Notifications not appearing"
**Diagnosis:** Database notifications might not be enabled
**Fix:** Check `config/filament.php` for notifications settings

---

## Test Results Template

Copy this template to document your test results:

```
TEST DATE: __________
TESTER: __________
ENVIRONMENT: Docker / Local / Production

Test Scenario 1: Super Admin Access
Result: ☐ PASS ☐ FAIL ☐ PARTIAL
Notes: _________________________________

Test Scenario 2: School Admin Access
Result: ☐ PASS ☐ FAIL ☐ PARTIAL
Notes: _________________________________

Test Scenario 3: School Isolation
Result: ☐ PASS ☐ FAIL ☐ PARTIAL
Notes: _________________________________

Test Scenario 4: Role-Based Access
Result: ☐ PASS ☐ FAIL ☐ PARTIAL
Notes: _________________________________

Test Scenario 5: Navbar Display
Result: ☐ PASS ☐ FAIL ☐ PARTIAL
Notes: _________________________________

Test Scenario 6: Global Query Scope
Result: ☐ PASS ☐ FAIL ☐ PARTIAL
Notes: _________________________________

Test Scenario 7: Create Assignment
Result: ☐ PASS ☐ FAIL ☐ PARTIAL
Notes: _________________________________

Test Scenario 8: Notifications
Result: ☐ PASS ☐ FAIL ☐ PARTIAL
Notes: _________________________________

Test Scenario 9: Audit Trail
Result: ☐ PASS ☐ FAIL ☐ PARTIAL
Notes: _________________________________

OVERALL: ☐ ALL PASS ☐ SOME FAIL ☐ REVIEW NEEDED

Sign-off: _________________________ Date: _________
```

---

## Automated Testing (Optional)

To add automated tests, create `tests/Feature/SchoolScopeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EquipmentAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_only_sees_their_school_assignments()
    {
        $schoolAdmin = User::factory()->create(['school_id' => 1]);
        $schoolAdmin->assignRole('school-admin');

        $this->actingAs($schoolAdmin);

        $assignments = EquipmentAssignment::all();

        $this->assertEquals(3, count($assignments));
        $this->assertTrue($assignments->every(fn($a) => $a->school_id === 1));
    }

    public function test_super_admin_sees_all_assignments()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin);

        $assignments = EquipmentAssignment::all();

        $this->assertGreaterThan(3, count($assignments));
    }
}
```

Run tests with:
```bash
docker compose exec app php artisan test
```

---

## Summary

All test scenarios should pass with:
- ✅ Proper school isolation
- ✅ Role-based access control
- ✅ Correct navbar display
- ✅ Query scoping working
- ✅ Notifications functioning
- ✅ Audit trail complete

For any failures, refer to troubleshooting section or check logs:
```bash
docker compose logs app | tail -100
```
