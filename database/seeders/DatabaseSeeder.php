<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Employee;
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
        $adminUser = User::firstOrCreate(
            ["email" => "admin@deped.gov.ph"],
            [
                "name" => "System Administrator",
                "password" => Hash::make("P@ssw0rd123"),
                "approval_status" => "approved",
            ]
        );
        $adminUser->assignRole("super-admin");

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

                    $employee = Employee::create($employeeData);

                    // ── Create User Account for the Submitter
                    $user = User::create([
                        'name' => $submittedBy,
                        'email' => $employee->email,
                        'password' => Hash::make('password'),
                        'school_id' => $school->id,
                        'approval_status' => 'approved',
                    ]);
                    $user->assignRole('school-admin');
                }

                $count++;
            }
            fclose($handle);
            $this->command->info("✅ Seeded {$count} schools and their admin staff.");
        }
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
