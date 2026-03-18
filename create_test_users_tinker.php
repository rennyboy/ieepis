<?php
use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\Hash;

echo "=== IEEPIS Test Users Creation ===\n\n";

// Get schools
$schools = School::all();
echo "Schools in database: " . $schools->count() . "\n";
foreach ($schools as $s) {
    echo "  - {$s->name} (ID: {$s->id})\n";
}
echo "\n";

// Test data - users to create
$testUsers = [
    // Super Admin
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

// Add school admins and technicians
$schoolAdminData = [
    ['school_name' => 'Davao City National High School', 'email' => 'admin.dcnhs@deped.gov.ph', 'tech_email' => 'tech.dcnhs@deped.gov.ph'],
    ['school_name' => 'Mintal National High School', 'email' => 'admin.mnhs@deped.gov.ph', 'tech_email' => 'tech.mnhs@deped.gov.ph'],
    ['school_name' => 'Tugbok District Science School', 'email' => 'admin.tdss@deped.gov.ph', 'tech_email' => 'tech.tdss@deped.gov.ph'],
    ['school_name' => 'Paquibato Elementary School', 'email' => 'admin.pes@deped.gov.ph', 'tech_email' => 'tech.pes@deped.gov.ph'],
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
        
        $testUsers[] = [
            'name' => "Technician - {$data['school_name']}",
            'email' => $data['tech_email'],
            'password' => 'P@ssw0rd123',
            'role' => 'technician',
            'school_id' => $school->id,
        ];
    }
}

// Create users
$created = 0;
$updated = 0;

foreach ($testUsers as $userData) {
    try {
        $user = User::where('email', $userData['email'])->first();
        
        if ($user) {
            $user->update([
                'name' => $userData['name'],
                'password' => Hash::make($userData['password']),
                'school_id' => $userData['school_id'],
            ]);
            echo "✓ Updated: {$userData['email']}\n";
            $updated++;
        } else {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'school_id' => $userData['school_id'],
            ]);
            echo "✓ Created: {$userData['email']}\n";
            $created++;
        }
        
        $user->syncRoles([$userData['role']]);
        
    } catch (Exception $e) {
        echo "✗ Error: {$e->getMessage()}\n";
    }
}

echo "\n=== Summary ===\n";
echo "Created: {$created}\n";
echo "Updated: {$updated}\n\n";

echo "=== All Users with Roles ===\n";
$allUsers = User::orderBy('email')->get();
foreach ($allUsers as $u) {
    $role = $u->getRoleNames()->first() ?? 'no-role';
    $school = $u->school?->name ?? 'SYSTEM';
    echo "{$u->email} | {$role} | {$school}\n";
}
echo "\n✓ Done!\n";
