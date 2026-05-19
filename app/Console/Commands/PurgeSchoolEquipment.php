<?php

namespace App\Console\Commands;

use App\Models\Equipment;
use App\Models\School;
use App\Scopes\SchoolScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeSchoolEquipment extends Command
{
    protected $signature = 'app:purge-school-equipment
        {--school= : schools.id whose equipment should be permanently deleted}
        {--dry-run : Show counts and the plan without deleting anything}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Permanently (force) delete all equipment belonging to a single school. Use to remove a wrong-school import so the data can be re-imported correctly.';

    public function handle(): int
    {
        $schoolId = (int) $this->option('school');

        if ($schoolId <= 0) {
            $this->error('--school=<id> is required.');

            return self::FAILURE;
        }

        $school = School::query()->withoutGlobalScopes()->find($schoolId);

        if (! $school) {
            $this->error("No school found with id {$schoolId}.");

            return self::FAILURE;
        }

        $connection = config('database.default');

        $this->newLine();
        $this->info("Target connection : {$connection}");
        $this->info('Target database   : '.config("database.connections.$connection.database"));
        $this->info('Target host       : '.config("database.connections.$connection.host"));
        $this->info("Target school     : [{$school->id}] {$school->name} ({$school->school_code})");
        $this->newLine();

        $base = fn () => Equipment::withoutGlobalScope(SchoolScope::class)
            ->withTrashed()
            ->where('school_id', $schoolId);

        $total = $base()->count();

        if ($total === 0) {
            $this->info('No equipment for this school — nothing to do.');

            return self::SUCCESS;
        }

        // property_no prefix breakdown so the operator can sanity-check the set.
        $breakdown = $base()
            ->get(['property_no'])
            ->groupBy(fn ($e) => substr((string) $e->property_no, 0, 12))
            ->map->count()
            ->sortDesc();

        $this->info("Equipment rows (incl. soft-deleted): {$total}");
        $this->table(
            ['property_no prefix', 'rows'],
            $breakdown->map(fn ($c, $p) => [$p, $c])->values()->all(),
        );

        // Pre-flight: refuse if anything still references these equipment ids.
        $ids = $base()->pluck('id');
        $refs = [
            'equipment_assignments' => DB::table('equipment_assignments')->whereIn('equipment_id', $ids)->count(),
            'documents' => DB::table('documents')->whereIn('equipment_id', $ids)->count(),
            'tickets' => DB::table('tickets')->whereIn('equipment_id', $ids)->count(),
            'maintenance_logs' => DB::table('maintenance_logs')->whereIn('equipment_id', $ids)->count(),
        ];

        $this->table(['Referencing table', 'Rows pointing at this equipment'], collect($refs)->map(fn ($c, $t) => [$t, $c])->values()->all());

        $blocking = collect($refs)->filter(fn ($c) => $c > 0);
        if ($blocking->isNotEmpty()) {
            $this->error('Aborting — these equipment rows are still referenced by: '.$blocking->keys()->implode(', ').'. Resolve those first.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->warn("Dry run — no changes made. Re-run without --dry-run to permanently delete {$total} row(s).");

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm("Permanently delete {$total} equipment row(s) for [{$school->id}] {$school->name}? This cannot be undone.")) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $deleted = 0;

        DB::transaction(function () use ($base, &$deleted) {
            $base()->select('id')->chunkById(500, function ($chunk) use (&$deleted) {
                $deleted += Equipment::withoutGlobalScope(SchoolScope::class)
                    ->withTrashed()
                    ->whereIn('id', $chunk->pluck('id'))
                    ->forceDelete();
            });
        });

        $remaining = $base()->count();

        $this->newLine();
        $this->info("Permanently deleted : {$deleted}");
        $this->info("Remaining for school: {$remaining}");

        return $remaining === 0 ? self::SUCCESS : self::FAILURE;
    }
}
