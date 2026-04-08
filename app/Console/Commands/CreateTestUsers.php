<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUsers extends Command
{
    protected $signature = "test:create-users";
    protected $description = "Create test user accounts for each school and roles";

    public function handle()
    {
        $this->info("=== IEEPIS Test Users Creation ===");
        $this->newLine();

        // Get schools
        $schools = School::all();
        $this->info("Schools in database: " . $schools->count());
        foreach ($schools as $s) {
            $this->info("  - {$s->name} (ID: {$s->id})");
        }
        $this->newLine();

        // Create core admin accounts (super & sdo)
        $created = 0;
        $updated = 0;

        $super = User::firstOrCreate(
            ["email" => "admin@deped.gov.ph"],
            [
                "name" => "System Administrator",
                "password" => Hash::make("P@ssw0rd123"),
                "approval_status" => "approved",
            ],
        );
        $super->assignRole("super-admin");

        $sdo = User::firstOrCreate(
            ["email" => "admin.sdo@deped.gov.ph"],
            [
                "name" => "SDO Administrator",
                "password" => Hash::make("P@ssw0rd123"),
                "approval_status" => "approved",
            ],
        );
        $sdo->assignRole("sdo-admin");

        // Create users from seeded employees (if email present), assign roles heuristically
        $this->info("Seeding users for employees based on Employee records...");
        $employees = Employee::whereNotNull("email")->get();

        foreach ($employees as $emp) {
            try {
                $email = trim(strtolower($emp->email));
                if (empty($email)) {
                    continue;
                }

                $fullName = trim(
                    ($emp->first_name ?? "") .
                        " " .
                        ($emp->middle_name ?? "") .
                        " " .
                        ($emp->last_name ?? ""),
                );

                $user = User::where("email", $email)->first();

                $defaultPassword = "P@ssw0rd123";

                if ($user) {
                    $user->update([
                        "name" => $fullName ?: $user->name,
                        "password" => Hash::make($defaultPassword),
                        "school_id" => $emp->school_id,
                        "approval_status" => "approved",
                    ]);
                    $this->line("✓ Updated: {$email}");
                    $updated++;
                } else {
                    $user = User::create([
                        "name" => $fullName ?: $emp->first_name ?? "User",
                        "email" => $email,
                        "password" => Hash::make($defaultPassword),
                        "school_id" => $emp->school_id,
                        "approval_status" => "approved",
                    ]);
                    $this->line("✓ Created: {$email}");
                    $created++;
                }

                // Heuristic role assignment (do not assign roles to all staff)
                $position = strtolower($emp->position ?? "");
                $employmentType = strtolower($emp->employment_type ?? "");

                $assignRole = null;

                if (
                    str_contains($position, "technician") ||
                    str_contains($position, "technician")
                ) {
                    $assignRole = "technician";
                } elseif (
                    str_contains($position, "head") ||
                    str_contains($position, "principal") ||
                    str_contains($position, "administrator") ||
                    str_contains($position, "coordinator") ||
                    str_contains($position, "head teacher")
                ) {
                    // Senior roles become school-admin
                    $assignRole = "school-admin";
                } elseif ($employmentType === "teaching") {
                    // Teachers will not be assigned elevated admin roles by default
                    $assignRole = null;
                }

                if ($assignRole) {
                    $user->syncRoles([$assignRole]);
                }
            } catch (\Exception $e) {
                $this->error(
                    "✗ Error with employee email {$emp->email}: {$e->getMessage()}",
                );
            }
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Users created from employees: {$created}");
        $this->info("Users updated from employees: {$updated}");
        $this->newLine();

        $this->info("=== All Users with Roles ===");
        $allUsers = User::orderBy("email")->get();
        foreach ($allUsers as $u) {
            $role = $u->getRoleNames()->first() ?? "no-role";
            $school = $u->school?->name ?? "SYSTEM";
            $this->info("{$u->email} | {$role} | {$school}");
        }

        $this->newLine();
        $this->info("✓ Employee-based users created/updated successfully!");
        $this->newLine();

        $this->info("Quick Credentials (default password):");
        $this->info("==================");
        $this->info("Password for seeded users: P@ssw0rd123");
        $this->info("Super Admin: admin@deped.gov.ph / P@ssw0rd123");
        $this->info("SDO Admin: admin.sdo@deped.gov.ph / P@ssw0rd123");
        $this->info("==================");
    }
}
