<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class CreateTechnician extends Command
{
    protected $signature = 'app:create-technician
        {--email=technician@deped.gov.ph : Email for the technician account}
        {--password=P@ssw0rd123 : Initial password (shared default)}
        {--force : Create even if a technician user already exists}';

    protected $description = 'Ensure at least one technician user exists (idempotent).';

    public function handle(): int
    {
        $existing = User::role('technician')->count();

        if ($existing > 0 && ! $this->option('force')) {
            $this->info("Technician users already exist ({$existing}). Nothing to do.");
            $this->line('Re-run with --force to add another anyway.');

            return self::SUCCESS;
        }

        $email = strtolower(trim((string) $this->option('email')));
        $password = (string) $this->option('password');

        // Defensive: guarantee the role exists for the web guard on a fresh DB.
        Role::findOrCreate('technician', 'web');

        // Technicians work across schools (see TicketPolicy / EquipmentPolicy),
        // so the account is intentionally not bound to a school_id.
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'password' => $password, // 'hashed' cast handles hashing
                'approval_status' => 'approved',
                'school_id' => null,
            ],
        );

        if (! $user->wasRecentlyCreated) {
            $user->update(['approval_status' => 'approved']);
        }

        if (! $user->hasRole('technician')) {
            $user->assignRole('technician');
        }

        $this->info(($user->wasRecentlyCreated ? 'Created' : 'Reused')." technician: {$email}");
        $this->warn("Password: {$password} — tell them to change it on first login.");

        return self::SUCCESS;
    }
}
