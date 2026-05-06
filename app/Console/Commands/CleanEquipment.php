<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanEquipment extends Command
{
    protected $signature = 'equipment:clean
        {--keep-employees : Keep the employees table intact}
        {--with-tickets : Also wipe tickets (otherwise tickets that referenced wiped equipment get NULLed)}
        {--keep-imports : Keep the storage/app/public/imports/ directory contents}
        {--dry-run : Show counts and plan without changing anything}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Wipe equipment, equipment_assignments, maintenance_logs (and by default employees) so the data can be re-imported manually.';

    public function handle(): int
    {
        $tables = ['equipment_assignments', 'maintenance_logs', 'equipment'];

        if (! $this->option('keep-employees')) {
            $tables[] = 'employees';
        }

        if ($this->option('with-tickets')) {
            array_unshift($tables, 'tickets');
        }

        $connection = config('database.default');
        $database = config("database.connections.$connection.database");
        $host = config("database.connections.$connection.host");

        $this->newLine();
        $this->info("Target connection : {$connection}");
        $this->info("Target host       : {$host}");
        $this->info("Target database   : {$database}");
        $this->newLine();

        $this->info('Plan — TRUNCATE … RESTART IDENTITY CASCADE on:');
        $this->table(
            ['Table', 'Current rows'],
            collect($tables)->map(fn ($t) => [$t, number_format(DB::table($t)->count())])->all(),
        );

        if (! $this->option('keep-imports')) {
            $disk = Storage::disk('public');
            $importCount = $disk->exists('imports') ? count($disk->files('imports')) : 0;
            $this->line("Will also delete {$importCount} file(s) from storage/app/public/imports/");
            $this->newLine();
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no changes made. Re-run without --dry-run to execute.');

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm("This will permanently delete the rows above from {$database}. Continue?", false)) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($tables) {
            foreach ($tables as $table) {
                DB::statement(sprintf('TRUNCATE TABLE %s RESTART IDENTITY CASCADE', $table));
                $this->info("✓ Truncated {$table}");
            }
        });

        if (! $this->option('keep-imports')) {
            $disk = Storage::disk('public');
            if ($disk->exists('imports')) {
                $files = $disk->files('imports');
                foreach ($files as $file) {
                    $disk->delete($file);
                }
                $this->info('✓ Removed '.count($files).' file(s) from imports/');
            }
        }

        $this->newLine();
        $this->info('Done. Verify with: php artisan equipment:clean --dry-run');

        return self::SUCCESS;
    }
}
