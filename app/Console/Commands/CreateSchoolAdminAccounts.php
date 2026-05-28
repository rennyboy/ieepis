<?php

namespace App\Console\Commands;

use App\Models\ApprovedUser;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateSchoolAdminAccounts extends Command
{
    protected $signature = 'app:create-school-admin-accounts
        {--dry-run : List eligible employees without creating accounts}
        {--password= : Override the shared default password (default: P@ssw0rd123)}
        {--all : Promote every employee with email, not just heads/principals/coordinators}';

    protected $description = 'Provision school-admin user accounts for eligible employees, scoped to their school.';

    public function handle(): int
    {
        $password = (string) ($this->option('password') ?: 'P@ssw0rd123');
        $dryRun = (bool) $this->option('dry-run');
        $promoteAll = (bool) $this->option('all');

        $employees = Employee::query()
            ->withoutGlobalScopes()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('school_id')
            ->whereNull('user_id')
            ->orderBy('school_id')
            ->orderBy('last_name')
            ->get();

        $eligible = $promoteAll
            ? $employees
            : $employees->filter(fn (Employee $e) => $this->isSchoolLeader($e))->values();

        $this->info(sprintf(
            'Eligible employees: %d (of %d employees with email but no linked user).',
            $eligible->count(),
            $employees->count(),
        ));

        if ($eligible->isEmpty()) {
            $this->warn('Nothing to do.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            foreach ($eligible as $emp) {
                $this->line(sprintf(
                    '  • %-40s %-30s school#%s',
                    $emp->email,
                    $emp->position ?? '?',
                    $emp->school_id,
                ));
            }
            $this->info('Dry run — no changes written.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Create {$eligible->count()} school-admin accounts with shared password \"{$password}\"?", true)) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $created = 0;
        $linkedExisting = 0;
        $skippedConflict = 0;
        $errors = 0;

        foreach ($eligible as $emp) {
            $email = strtolower(trim((string) $emp->email));

            try {
                DB::transaction(function () use ($emp, $email, $password, &$created, &$linkedExisting, &$skippedConflict) {
                    $existing = User::query()->where('email', $email)->first();

                    if ($existing) {
                        $otherEmployee = Employee::query()
                            ->withoutGlobalScopes()
                            ->where('user_id', $existing->id)
                            ->where('id', '!=', $emp->id)
                            ->first();

                        if ($otherEmployee) {
                            $this->warn(sprintf(
                                '  ! %s already linked to employee #%d — skipping.',
                                $email,
                                $otherEmployee->id,
                            ));
                            $skippedConflict++;

                            return;
                        }

                        $user = $existing;
                        $linkedExisting++;
                    } else {
                        $user = User::create([
                            'email' => $email,
                            'password' => Hash::make($password),
                            'approval_status' => 'approved',
                            'school_id' => $emp->school_id,
                        ]);
                        $created++;
                    }

                    if (! $user->hasRole('school-admin')) {
                        $user->assignRole('school-admin');
                    }

                    $emp->update(['user_id' => $user->id]);

                    ApprovedUser::firstOrCreate(
                        ['email' => $email],
                        [
                            'role' => 'school-admin',
                            'school_id' => $emp->school_id,
                            'status' => 'approved',
                            'actioned_at' => now(),
                            'notes' => 'Auto-provisioned by app:create-school-admin-accounts',
                        ],
                    );

                    $this->line("  ✓ {$email}");
                });
            } catch (\Throwable $e) {
                $errors++;
                $this->error("  ✗ {$email}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Created new users:        {$created}");
        $this->info("Linked existing users:    {$linkedExisting}");
        $this->info("Skipped (email taken):    {$skippedConflict}");
        if ($errors > 0) {
            $this->warn("Errors:                   {$errors}");
        }

        $this->newLine();
        $this->warn('Shared password issued. Tell each user to change it on first login.');

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isSchoolLeader(Employee $emp): bool
    {
        $position = strtolower((string) ($emp->position ?? ''));

        if ($position === '') {
            return false;
        }

        return str_contains($position, 'head')
            || str_contains($position, 'principal')
            || str_contains($position, 'administrator')
            || str_contains($position, 'coordinator');
    }
}
