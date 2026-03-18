<?php

/**
 * IEEPIS Test Users Creation Script
 *
 * This script creates test user accounts for each school to verify
 * permission isolation and role-based access control.
 *
 * Run in Tinker:
 * docker exec -it ieepis-app php artisan tinker
 * include('/var/www/create_test_users.php');
 */

use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\Hash;

echo "=== IEEPIS Test Users Creation ===\n\n";

// Get schools
$schools = School::all();

// Test data - users to create
$testUsers = [
    // Super Admin (should already exist, but we'll ensure it)
    [
        'name' => 'System Administrator',
        'email' => 'admin@deped.gov.ph',
        'password' => 'P@ssw0rd123',
        'role' => 'super-admin',
        'school_id' => null,
    ],

    // SDO Admin
    [
        'name' => 'SDO Administrator',
        'email' => 'admin.sdo@deped.gov.ph',
        'password' => 'P@ssw0rd123',
        'role' => 'sdo-admin',
        'school_id' => null,
    ],
];

// Add school admins for each school
$schoolAdminData = [
    ['school_name' => 'Davao City National High School', 'email' => 'admin.dcnhs@deped.gov.ph', 'role_prefix' => 'DCNHS'],
    ['school_name' => 'Mintal National High School', 'email' => 'admin.mnhs@deped.gov.ph', 'role_prefix' => 'MNHS'],
    ['school_name' => 'Tugbok District Science School', 'email' => 'admin.tdss@deped.gov.ph', 'role_prefix' => 'TDSS'],
    ['school_name' => 'Paquibato Elementary School', 'email' => 'admin.pes@deped.gov.ph', 'role_prefix' => 'PES'],
];

foreach ($schoolAdminData as $data) {
    $school = School::where('name', $data['school_name'])->first();
    if ($school) {
        $testUsers[] = [
            'name' => "Admin - {$data['school_name']}",
            'email' => $data['email'],
            'password' => 'P@ssw0rd123',
            'role' => 'school-admin',
            'school_id' => $school->id,
        ];

        // Add technician for this school
        $techEmail = str_replace('admin.', 'tech.', $data['email']);
        $testUsers[] = [
            'name' => "Technician - {$data['school_name']}",
            'email' => $techEmail,
            'password' => 'P@ssw0rd123',
            'role' => 'technician',
            'school_id' => $school->id,
        ];
    }
}

// Create users
$created = 0;
$updated = 0;
$skipped = 0;

foreach ($testUsers as $userData) {
    try {
        $user = User::firstOrNew(['email' => $userData['email']]);

        if ($user->exists) {
            $user->update([
                'name' => $userData['name'],
                'password' => Hash::make($userData['password']),
                'school_id' => $userData['school_id'],
            ]);
            echo "✓ Updated: {$userData['email']} ({$userData['role']})\n";
            $updated++;
        } else {
            $user->fill([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'school_id' => $userData['school_id'],
            ])->save();
            echo "✓ Created: {$userData['email']} ({$userData['role']})\n";
            $created++;
        }

        // Assign role
        $user->syncRoles([$userData['role']]);

    } catch (\Exception $e) {
        echo "✗ Error with {$userData['email']}: {$e->getMessage()}\n";
        $skipped++;
    }
}

echo "\n=== Summary ===\n";
echo "Created: {$created}\n";
echo "Updated: {$updated}\n";
echo "Errors: {$skipped}\n";
echo "Total: " . (count($testUsers)) . "\n\n";

// Verify all users with their roles
echo "=== Verification: All Users with Roles ===\n";
$allUsers = User::orderBy('email')->get();
foreach ($allUsers as $user) {
    $role = $user->getRoleNames()->first() ?? 'no-role';
    $schoolName = $user->school?->name ?? 'No School';
    echo "{$user->email} | Role: {$role} | School: {$schoolName}\n";
}

echo "\n✓ Test users created successfully!\n";
echo "\nTest Credentials:\n";
echo "==================\n";
echo "Super Admin: admin@deped.gov.ph / P@ssw0rd123\n";
echo "SDO Admin: admin.sdo@deped.gov.ph / P@ssw0rd123\n";
echo "School Admins:\n";
echo "  - admin.dcnhs@deped.gov.ph / P@ssw0rd123 (Davao City NHS)\n";
echo "  - admin.mnhs@deped.gov.ph / P@ssw0rd123 (Mintal NHS)\n";
echo "  - admin.tdss@deped.gov.ph / P@ssw0rd123 (Tugbok DSS)\n";
echo "  - admin.pes@deped.gov.ph / P@ssw0rd123 (Paquibato ES)\n";
echo "Technicians: tech.[school-code]@deped.gov.ph / P@ssw0rd123\n";
echo "==================\n";
