<?php

use App\Models\Employee;
use App\Models\User;
use App\Models\ApprovedUser;
use App\Models\School;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Ensure we are running in a Laravel environment
if (!defined('LARAVEL_START')) {
    echo "This script must be run via php artisan tinker or from within the Laravel application context.\n";
    exit(1);
}

echo "Starting synchronization of 61 employees to users...\n";

$employees = Employee::withoutGlobalScopes()->get();
$count = $employees->count();

echo "Found $count employees.\n";

$processed = 0;
$createdUsers = 0;
$updatedUsers = 0;

foreach ($employees as $employee) {
    echo "Processing: {$employee->full_name} ({$employee->email})...\n";

    $school = $employee->school;
    $district = $school?->district()->first();
    $divisionId = $district?->division_id;
    $divisionName = $school?->division;

    // 1. Sync ApprovedUser (Whitelist)
    $approvedUser = ApprovedUser::updateOrCreate(
        ['email' => $employee->email],
        [
            'name' => $employee->full_name,
            'role' => 'school-admin',
            'status' => 'approved',
            'division' => $divisionName,
            'division_id' => $divisionId,
            'actioned_at' => now(),
            'notes' => 'Auto-approved from Employee record.'
        ]
    );

    // 2. Sync User
    $userExists = User::where('email', $employee->email)->exists();
    
    $user = User::updateOrCreate(
        ['email' => $employee->email],
        [
            'name' => $employee->full_name,
            // Only set password for new users to avoid overwriting existing ones
            'password' => $userExists ? User::where('email', $employee->email)->first()->password : Hash::make('password'),
            'school_id' => $employee->school_id,
            'approval_status' => 'approved',
            'division' => $divisionName,
            'division_id' => $divisionId,
        ]
    );

    if ($userExists) {
        $updatedUsers++;
    } else {
        $createdUsers++;
    }

    // 3. Assign Role
    if (!$user->hasRole('school-admin')) {
        $user->assignRole('school-admin');
    }

    $processed++;
}

echo "\nSynchronization Complete!\n";
echo "Total Employees Processed: $processed\n";
echo "New Users Created: $createdUsers\n";
echo "Existing Users Updated: $updatedUsers\n";
echo "Default password for new users is: password\n";
