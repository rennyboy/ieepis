<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\School;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUsers extends Command
{
    protected $signature = 'test:create-users';

    protected $description = 'Create test user accounts for each school and assign roles heuristically';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('This command is intended for local development only. Aborting.');

            return self::FAILURE;
        }

        $defaultPassword = 'P@ssw0rd123';

        $this->info('=== IEEPIS Test Users Creation ===');
        $this->newLine();

        $this->info('Schools in database: '.School::count());

        $super = User::firstOrCreate(
            ['email' => 'admin@deped.gov.ph'],
            ['password' => Hash::make($defaultPassword), 'approval_status' => 'approved'],
        );
        $super->assignRole('super-admin');

        $sdo = User::firstOrCreate(
            ['email' => 'admin.sdo@deped.gov.ph'],
            ['password' => Hash::make($defaultPassword), 'approval_status' => 'approved'],
        );
        $sdo->assignRole('sdo-admin');

        $this->info('Seeding users for employees with email present...');

        $created = $updated = 0;
        $employees = Employee::query()->whereNotNull('email')->get();

        foreach ($employees as $emp) {
            $email = trim(strtolower($emp->email));
            if ($email === '') {
                continue;
            }

            try {
                $user = User::query()->firstOrCreate(
                    ['email' => $email],
                    ['password' => Hash::make($defaultPassword), 'approval_status' => 'approved'],
                );

                if ($user->wasRecentlyCreated) {
                    $created++;
                    $this->line("✓ Created: {$email}");
                } else {
                    $user->update(['password' => Hash::make($defaultPassword), 'approval_status' => 'approved']);
                    $updated++;
                    $this->line("✓ Updated: {$email}");
                }

                if ($emp->user_id !== $user->id) {
                    Employee::query()->where('user_id', $user->id)->update(['user_id' => null]);
                    $emp->update(['user_id' => $user->id]);
                }

                $role = $this->guessRole($emp);
                if ($role !== null) {
                    $user->syncRoles([$role]);
                }
            } catch (\Throwable $e) {
                $this->error("✗ Error with {$email}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Users created: {$created}");
        $this->info("Users updated: {$updated}");
        $this->newLine();

        $this->info('=== All Users with Roles ===');
        foreach (User::query()->orderBy('email')->get() as $u) {
            $role = $u->getRoleNames()->first() ?? 'no-role';
            $school = $u->school?->name ?? 'SYSTEM';
            $this->info("{$u->email} | {$role} | {$school}");
        }

        $this->newLine();
        $this->info('Default password for all seeded users: '.$defaultPassword);

        return self::SUCCESS;
    }

    private function guessRole(Employee $emp): ?string
    {
        $position = strtolower($emp->position ?? '');
        $employmentType = strtolower($emp->employment_type ?? '');

        if (str_contains($position, 'technician')) {
            return 'technician';
        }

        if (
            str_contains($position, 'head') ||
            str_contains($position, 'principal') ||
            str_contains($position, 'administrator') ||
            str_contains($position, 'coordinator')
        ) {
            return 'school-admin';
        }

        if ($employmentType === 'teaching') {
            return null;
        }

        return null;
    }
}
