<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanEmployeesKeepAdmins extends Command
{
    protected $signature = 'employees:clean-keep-admins
        {--dry-run : Show counts and plan without deleting anything}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Force-delete all employees except those linked to a school-admin user (one administrator per school), so employees can be re-imported fresh without unique-constraint conflicts.';

    public function handle(): int
    {
        $connection = config('database.default');
        $database = config("database.connections.$connection.database");

        // Employees to KEEP: linked to a user that holds the school-admin role.
        $adminUserIds = User::role('school-admin')->pluck('id');

        $base = fn () => Employee::query()->withoutGlobalScopes();

        $total = $base()->count();
        $keep = $base()->whereIn('user_id', $adminUserIds)->count();
        $delete = $total - $keep;

        $this->newLine();
        $this->info("Target database : {$database}");
        $this->table(['Metric', 'Count'], [
            ['Employees total', number_format($total)],
            ['Keep (school-admin linked)', number_format($keep)],
            ['Delete (everyone else)', number_format($delete)],
        ]);

        // Blocking FKs: equipment_assignments.employee_id is restrictOnDelete,
        // so any assignment whose officer is a to-be-deleted employee aborts
        // the whole delete. Surface it instead of failing mid-transaction.
        $deletableIds = $base()->whereNotIn('user_id', $adminUserIds)
            ->orWhereNull('user_id')
            ->pluck('id');

        $blocking = DB::table('equipment_assignments')
            ->whereIn('employee_id', $deletableIds)
            ->count();

        if ($blocking > 0) {
            $this->error("{$blocking} equipment_assignments reference employees marked for deletion (employee_id is restrictOnDelete).");
            $this->line('Wipe or reassign those assignments first (see: php artisan equipment:clean).');

            return self::FAILURE;
        }

        if ($delete === 0) {
            $this->info('Nothing to delete.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no changes made. Re-run without --dry-run to execute.');

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm("Permanently force-delete {$delete} employee row(s) from {$database}, keeping {$keep} school-admin(s). Continue?", false)) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        // forceDelete (not delete) — Employee uses SoftDeletes and
        // employee_number carries an unscoped UNIQUE index, so a soft delete
        // would still collide with a fresh re-import. Hard delete frees the
        // index. withoutGlobalScopes() already includes trashed rows, so this
        // also purges any rows soft-deleted by an earlier run.
        $deleted = DB::transaction(function () use ($base, $adminUserIds) {
            return $base()
                ->where(function ($q) use ($adminUserIds) {
                    $q->whereNull('user_id')
                        ->orWhereNotIn('user_id', $adminUserIds);
                })
                ->forceDelete();
        });

        $this->newLine();
        $this->info("✓ Done. Deleted {$deleted} employee row(s); {$keep} school-admin(s) retained.");
        $this->line('Verify with: php artisan employees:clean-keep-admins --dry-run');

        return self::SUCCESS;
    }
}
