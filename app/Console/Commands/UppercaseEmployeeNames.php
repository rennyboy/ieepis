<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Scopes\SchoolScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UppercaseEmployeeNames extends Command
{
    protected $signature = 'employees:uppercase-names
        {--dry-run : Show how many rows would change without writing anything}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Backfill existing employees so first/last/middle name and suffix are stored uppercase, matching EmployeeImport normalization.';

    private const NAME_FIELDS = ['first_name', 'last_name', 'middle_name', 'suffix'];

    public function handle(): int
    {
        $connection = config('database.default');
        $database = config("database.connections.$connection.database");

        // Bypass SchoolScope so every tenant's records are normalized, not just
        // the running user's school (CLI has no authenticated user anyway).
        $base = fn () => Employee::withoutGlobalScope(SchoolScope::class);

        $total = $base()->count();
        $this->newLine();
        $this->info("Target database : {$database}");
        $this->info('Employees total : '.number_format($total));
        $this->newLine();

        $toChange = 0;
        $base()->select('id', ...self::NAME_FIELDS)
            ->chunkById(500, function ($employees) use (&$toChange) {
                foreach ($employees as $employee) {
                    if ($this->upperedDiff($employee) !== []) {
                        $toChange++;
                    }
                }
            });

        $this->line('Rows needing normalization: '.number_format($toChange));

        if ($toChange === 0) {
            $this->info('Nothing to do — all names already uppercase.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no changes made. Re-run without --dry-run to execute.');

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm("Uppercase names on {$toChange} employee row(s) in {$database}. Continue?", false)) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $updated = 0;
        DB::transaction(function () use ($base, &$updated) {
            $base()->select('id', ...self::NAME_FIELDS)
                ->chunkById(500, function ($employees) use (&$updated) {
                    foreach ($employees as $employee) {
                        $diff = $this->upperedDiff($employee);
                        if ($diff === []) {
                            continue;
                        }
                        Employee::withoutGlobalScope(SchoolScope::class)
                            ->whereKey($employee->id)
                            ->update($diff);
                        $updated++;
                    }
                });
        });

        $this->newLine();
        $this->info("✓ Done. Updated {$updated} employee row(s).");
        $this->line('Verify with: php artisan employees:uppercase-names --dry-run');

        return self::SUCCESS;
    }

    /**
     * Return only the name fields whose value would change when uppercased,
     * so updates and the change count touch nothing already normalized.
     */
    private function upperedDiff(Employee $employee): array
    {
        $diff = [];
        foreach (self::NAME_FIELDS as $field) {
            $value = $employee->getAttribute($field);
            if ($value === null || $value === '') {
                continue;
            }
            $upper = Str::upper($value);
            if ($upper !== $value) {
                $diff[$field] = $upper;
            }
        }

        return $diff;
    }
}
