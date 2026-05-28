<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Employee;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Create essential roles
        Role::firstOrCreate(["name" => "super-admin", "guard_name" => "web"]);
        Role::firstOrCreate(["name" => "sdo-admin", "guard_name" => "web"]);
        Role::firstOrCreate(["name" => "school-admin", "guard_name" => "web"]);
        Role::firstOrCreate(["name" => "technician", "guard_name" => "web"]);

        // ── 2. Create the Super Admin
        $adminPassword = app()->environment('local')
            ? 'P@ssw0rd123'
            : (env('SEED_SUPER_ADMIN_PASSWORD') ?: Str::random(24));

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@deped.gov.ph'],
            [
                'password' => Hash::make($adminPassword),
                'approval_status' => 'approved',
            ]
        );
        $adminUser->assignRole('super-admin');

        if (! app()->environment('local')) {
            $this->command->warn("Super admin password (capture now, will not repeat): {$adminPassword}");
        }

        // ── 3. Parse the CSV file from the root directory
        $csvPath = base_path("LIST OF EMPLOYEES 03.09.csv");

        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            return;
        }

        $this->command->info("Seeding data from root CSV...");

        if (($handle = fopen($csvPath, "r")) !== false) {
            // Read header: School/Office Name,Division,District,Region,Submitted By,email
            $header = fgetcsv($handle);
            $count = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 5) continue;

                $schoolName = trim($row[0]);
                $divisionName = trim($row[1]);
                $districtName = trim($row[2]);
                $regionName = trim($row[3]);
                $submittedBy = trim($row[4]);
                $staffEmail = trim($row[5] ?? "");

                if (empty($schoolName)) continue;

                // ── Create School
                $school = School::firstOrCreate(
                    ['name' => $schoolName],
                    [
                        'school_code' => $this->generateSchoolCode($schoolName),
                        'division' => $divisionName,
                        'district' => $districtName ?: 'Unassigned',
                        'region' => $regionName,
                        'status' => 'active',
                        'city_municipality' => 'Dapitan City',
                    ]
                );

                // ── Create Admin Staff (Employee) if name exists
                if (!empty($submittedBy)) {
                    $nameParts = explode(' ', $submittedBy);
                    $lastName = array_pop($nameParts);
                    $firstName = implode(' ', $nameParts) ?: 'Staff';

                    $employeeData = [
                        'school_id' => $school->id,
                        'employee_number' => 'EMP-' . Str::upper(Str::random(6)),
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'position' => 'School Admin / Submitter',
                        'department' => 'Administration',
                        'employment_type' => 'non-teaching',
                        'status' => 'active',
                        'email' => $staffEmail ?: Str::slug($submittedBy) . '@ieepis.local',
                        'date_hired' => now(),
                    ];

                    $user = User::create([
                        'email' => $employeeData['email'],
                        'password' => Hash::make(Str::random(20)),
                        'approval_status' => 'approved',
                    ]);
                    $user->assignRole('school-admin');

                    $employeeData['user_id'] = $user->id;
                    Employee::create($employeeData);
                }

                $count++;
            }
            fclose($handle);
            $this->command->info("✅ Seeded {$count} schools and their admin staff.");
        }

        $this->seedEquipment();
    }

    private function seedEquipment(): void
    {
        $brands = ['Lenovo', 'Acer', 'Dell', 'HP'];
        $types = ['Laptop', 'Desktop', 'TV', 'Router'];
        $totalEquipment = 0;

        foreach (School::all() as $school) {
            foreach ($types as $type) {
                for ($i = 0; $i < 3; $i++) {
                    Equipment::create([
                        'school_id' => $school->id,
                        'property_no' => 'PROP-' . Str::upper(Str::random(10)),
                        'serial_number' => 'SER-' . Str::upper(Str::random(10)),
                        'item_type' => $type,
                        'equipment_type' => $type,
                        'brand' => $brands[array_rand($brands)],
                        'model' => "{$type}-2026",
                        'is_dcp' => true,
                        'dcp_package' => 'Batch 2026',
                        'accountability_status' => 'unassigned',
                        'is_functional' => true,
                        'condition' => 'Good',
                    ]);
                    $totalEquipment++;
                }
            }
        }

        $this->command->info("✅ Seeded {$totalEquipment} equipment items across schools.");
    }

    /**
     * Generate a unique school code based on name.
     */
    private function generateSchoolCode(string $name): string
    {
        preg_match_all('/\b\w/', $name, $matches);
        $initials = implode('', $matches[0]);
        // Remove parentheses and their contents from initials if any
        $initials = preg_replace('/\([^)]*\)/', '', $initials);
        return strtoupper(substr($initials, 0, 5)) . '-' . rand(1000, 9999);
    }
}
