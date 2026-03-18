<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUsers extends Command
{
    protected $signature = 'test:create-users';
    protected $description = 'Create test user accounts for each school and roles';

    public function handle()
    {
        $this->info('=== IEEPIS Test Users Creation ===');
        $this->newLine();

        // Get schools
        $schools = School::all();
        $this->info("Schools in database: " . $schools->count());
        foreach ($schools as $s) {
            $this->info("  - {$s->name} (ID: {$s->id})");
        }
        $this->newLine();

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
                    $this->line("✓ Updated: {$userData['email']}");
                    $updated++;
                } else {
                    $user = User::create([
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'password' => Hash::make($userData['password']),
                        'school_id' => $userData['school_id'],
                    ]);
                    $this->line("✓ Created: {$userData['email']}");
                    $created++;
                }

                $user->syncRoles([$userData['role']]);

            } catch (\Exception $e) {
                $this->error("✗ Error with {$userData['email']}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Created: {$created}");
        $this->info("Updated: {$updated}");
        $this->newLine();

        $this->info('=== All Users with Roles ===');
        $allUsers = User::orderBy('email')->get();
        foreach ($allUsers as $u) {
            $role = $u->getRoleNames()->first() ?? 'no-role';
            $school = $u->school?->name ?? 'SYSTEM';
            $this->info("{$u->email} | {$role} | {$school}");
        }

        $this->newLine();
        $this->info('✓ Test users created successfully!');
        $this->newLine();
        $this->info('Test Credentials:');
        $this->info('==================');
        $this->info('Super Admin: admin@deped.gov.ph / P@ssw0rd123');
        $this->info('SDO Admin: admin.sdo@deped.gov.ph / P@ssw0rd123');
        $this->info('School Admins:');
        $this->info('  - admin.dcnhs@deped.gov.ph / P@ssw0rd123 (Davao City NHS)');
        $this->info('  - admin.mnhs@deped.gov.ph / P@ssw0rd123 (Mintal NHS)');
        $this->info('  - admin.tdss@deped.gov.ph / P@ssw0rd123 (Tugbok DSS)');
        $this->info('  - admin.pes@deped.gov.ph / P@ssw0rd123 (Paquibato ES)');
        $this->info('Technicians:');
        $this->info('  - tech.dcnhs@deped.gov.ph / P@ssw0rd123 (DCNHS)');
        $this->info('  - tech.mnhs@deped.gov.ph / P@ssw0rd123 (MNHS)');
        $this->info('  - tech.tdss@deped.gov.ph / P@ssw0rd123 (TDSS)');
        $this->info('  - tech.pes@deped.gov.ph / P@ssw0rd123 (PES)');
        $this->info('==================');
    }
}
