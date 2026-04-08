<?php

namespace Database\Seeders;

use App\Models\ApprovedUser;
use App\Models\District;
use App\Models\Division;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentAssignment;
use App\Models\InternetConnection;
use App\Models\School;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * DapitanSchoolsSeeder
 *
 * Imports the official Dapitan City school list from the CSV:
 *   "LIST OF EMPLOYEES 03.09.csv"
 *
 * CSV columns (updated):
 *   0 - School/Office Name  (e.g. "Aliguay Integrated School (501789)")
 *   1 - Division            (e.g. "Dapitan City")
 *   2 - District            (e.g. "SULANGON")
 *   3 - Region              (e.g. "Region IX")
 *   4 - Submitted By        (ICT Coordinator name)
 *
 * This seeder:
 *  1. Creates roles.
 *  2. Preserves super-admin.
 *  3. Clears old dummy data.
 *  4. Creates Division "Dapitan City" with 5 Districts.
 *  5. Imports each school linked to its District.
 *  6. Creates an ICT Coordinator employee per school (from Submitted By).
 *  7. Creates an Administrative Officer employee per school.
 *  8. Creates a User (school-admin) + ApprovedUser for each coordinator.
 */
class DapitanSchoolsSeeder extends Seeder
{
    private const CSV_FILENAME = 'LIST OF EMPLOYEES 03.09.csv';

    public function run(): void
    {
        // ── 1. Roles ─────────────────────────────────────────────────────────
        foreach (['super-admin', 'sdo-admin', 'school-admin', 'technician', 'viewer'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // ── 2. Super-admin ───────────────────────────────────────────────────
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@deped.gov.ph'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('P@ssw0rd123'),
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles(['super-admin']);

        // ── 3. Wipe old data ─────────────────────────────────────────────────
        $this->command->info('🧹 Clearing old data...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        EquipmentAssignment::query()->delete();
        Document::query()->delete();
        Ticket::query()->delete();
        InternetConnection::query()->delete();
        Employee::withoutGlobalScopes()->delete();
        Equipment::query()->delete();

        User::where('id', '!=', $superAdmin->id)->forceDelete();
        ApprovedUser::query()->delete();
        School::query()->forceDelete();
        District::query()->delete();
        Division::query()->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('✅ Old data cleared.');

        // ── 4. Division & Districts ──────────────────────────────────────────
        $division = Division::create([
            'name' => 'Dapitan City',
            'region' => 'Region IX',
            'code' => 'DIV-DAPITAN',
            'is_active' => true,
        ]);

        $districtNames = [
            'BARCELONA',
            'BAYLIMANGO',
            'DAPITAN CITY CENTRAL',
            'POTUNGAN',
            'SULANGON',
        ];

        $districts = [];
        foreach ($districtNames as $name) {
            $districts[$name] = District::create([
                'name' => Str::title($name),
                'division_id' => $division->id,
                'division' => 'Dapitan City',
                'region' => 'Region IX',
                'code' => 'DIST-'.strtoupper(Str::slug($name)),
                'is_active' => true,
            ]);
            $this->command->line("  📍 District: {$name}");
        }

        $this->command->info("✅ Division + {$division->name} (5 districts) created.");

        // ── 5. Parse CSV ─────────────────────────────────────────────────────
        $csvPath = base_path(self::CSV_FILENAME);

        if (! file_exists($csvPath)) {
            $this->command->error("❌ CSV not found: {$csvPath}");

            return;
        }

        $handle = fopen($csvPath, 'r');
        fgetcsv($handle); // skip header

        $schoolCount = 0;
        $userCount = 0;
        $employeeCount = 0;
        $empCounter = 1; // for generating unique employee numbers

        while (($row = fgetcsv($handle)) !== false) {
            $rawName = trim($row[0] ?? '');
            $divisionRaw = trim($row[1] ?? '');
            $districtRaw = strtoupper(trim($row[2] ?? ''));
            $region = trim($row[3] ?? '');
            $submittedBy = trim($row[4] ?? '');

            // Skip empty rows
            if (empty($rawName) || empty($divisionRaw) || empty($region)) {
                continue;
            }

            // ── Parse school code from "School Name (123456)" ────────────────
            $schoolCode = null;
            $schoolName = $rawName;

            if (preg_match('/^(.+?)\s*\((\d+)\)\s*$/', $rawName, $matches)) {
                $schoolName = trim($matches[1]);
                $schoolCode = $matches[2];
            }

            if (empty($schoolCode)) {
                $schoolCode = strtoupper(Str::slug($schoolName));
            }

            // Resolve district_id
            $districtId = null;
            if (! empty($districtRaw) && isset($districts[$districtRaw])) {
                $districtId = $districts[$districtRaw]->id;
            }

            // ── Create School ────────────────────────────────────────────────
            $school = School::updateOrCreate(
                ['school_code' => $schoolCode],
                [
                    'name' => $schoolName,
                    'school_id_number' => is_numeric($schoolCode) ? $schoolCode : null,
                    'region' => $region,
                    'division' => $divisionRaw,
                    'district' => ! empty($districtRaw) ? Str::title($districtRaw) : null,
                    'district_id' => $districtId,
                    'city_municipality' => $divisionRaw,
                    'province' => 'Zamboanga del Norte',
                    'governance_level' => 'School',
                    'status' => 'active',
                ]
            );

            $schoolCount++;
            $districtLabel = ! empty($districtRaw) ? " [{$districtRaw}]" : '';
            $this->command->line("  🏫 [{$schoolCode}] {$schoolName}{$districtLabel}");

            // ── Create Administrative Officer (employee) ─────────────────────
            $aoNumber = sprintf('AO-%s-%04d', date('Y'), $empCounter++);
            Employee::withoutGlobalScopes()->updateOrCreate(
                ['employee_number' => $aoNumber],
                [
                    'school_id' => $school->id,
                    'first_name' => 'Administrative',
                    'last_name' => 'Officer',
                    'middle_name' => null,
                    'position' => 'Administrative Officer V',
                    'department' => 'Administration',
                    'employment_type' => 'non-teaching',
                    'status' => 'active',
                    'date_hired' => now()->subYears(rand(2, 10))->subDays(rand(0, 365)),
                    'email' => 'ao.'.Str::slug($schoolName).'@deped.gov.ph',
                ]
            );
            $employeeCount++;

            // ── Create ICT Coordinator (employee) from "Submitted By" ────────
            if (! empty($submittedBy)) {
                $nameParts = array_values(array_filter(array_map('trim', explode(' ', $submittedBy))));
                $firstName = Str::title(Str::lower($nameParts[0] ?? 'ICT'));
                $lastName = Str::title(Str::lower(end($nameParts)));
                $middleName = null;

                if (count($nameParts) > 2) {
                    // Middle parts between first and last
                    $middleParts = array_slice($nameParts, 1, -1);
                    $middleName = Str::title(Str::lower(implode(' ', $middleParts)));
                }

                $ictNumber = sprintf('ICT-%s-%04d', date('Y'), $empCounter++);
                $emailLocal = Str::lower(implode('.', $nameParts));
                $emailLocal = preg_replace('/[^a-z0-9.]/', '', $emailLocal);
                $coordinatorEmail = "{$emailLocal}@deped.gov.ph";

                Employee::withoutGlobalScopes()->updateOrCreate(
                    ['employee_number' => $ictNumber],
                    [
                        'school_id' => $school->id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'middle_name' => $middleName,
                        'position' => 'ICT Coordinator',
                        'department' => 'ICT',
                        'employment_type' => 'non-teaching',
                        'status' => 'active',
                        'date_hired' => now()->subYears(rand(1, 8))->subDays(rand(0, 365)),
                        'email' => $coordinatorEmail,
                    ]
                );
                $employeeCount++;

                $this->command->line("     👤 ICT: {$firstName} {$lastName} → {$coordinatorEmail}");

                // ── User account (school-admin) ──────────────────────────────
                $properName = Str::title(Str::lower($submittedBy));

                $coordinator = User::updateOrCreate(
                    ['email' => $coordinatorEmail],
                    [
                        'name' => $properName,
                        'password' => Hash::make('P@ssw0rd123'),
                        'school_id' => $school->id,
                        'approval_status' => 'approved',
                        'email_verified_at' => now(),
                    ]
                );
                $coordinator->syncRoles(['school-admin']);

                // ── ApprovedUser whitelist ────────────────────────────────────
                ApprovedUser::updateOrCreate(
                    ['email' => $coordinatorEmail],
                    [
                        'name' => $properName,
                        'role' => 'school-admin',
                        'division' => $divisionRaw,
                        'status' => 'approved',
                    ]
                );

                $userCount++;
            }

            // ── Administrative Officer line ──────────────────────────────────
            $this->command->line("     🧑‍💼 AO: Administrative Officer → {$school->name}");
        }

        fclose($handle);

        // ── Summary ──────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('  ✅  DAPITAN SCHOOLS IMPORT COMPLETE');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info("  📌  Division     : {$division->name}");
        $this->command->info('  📍  Districts    : 5');
        $this->command->info("  🏫  Schools      : {$schoolCount}");
        $this->command->info("  👥  Employees    : {$employeeCount} (AO + ICT per school)");
        $this->command->info("  👤  User accounts: {$userCount} (school-admin)");
        $this->command->newLine();
        $this->command->info('  🔑  Default password: P@ssw0rd123');
        $this->command->info('═══════════════════════════════════════════════════════');
    }
}
