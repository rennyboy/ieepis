<?php

namespace App\Console\Commands;

use App\Imports\EquipmentImport;
use App\Models\School;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Finder\Finder;

class BulkImportEquipment extends Command
{
    protected $signature = 'equipment:bulk-import
        {path : Folder containing {school_code}.xlsx files, OR a single file}
        {--school= : Override school_code lookup (single-file use)}
        {--dry-run : Show file→school mapping, no writes}
        {--force : Skip the global confirmation prompt}';

    protected $description = 'Bulk-import equipment from a folder of {school_code}.xlsx files (one per school).';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("Path does not exist: {$path}");

            return self::FAILURE;
        }

        $files = is_dir($path) ? $this->collectFiles($path) : [$path];

        if (empty($files)) {
            $this->warn('No .xlsx / .xls / .csv files found.');

            return self::SUCCESS;
        }

        $plan = collect($files)->map(fn (string $file) => $this->resolveFile($file))->all();

        $this->newLine();
        $this->info('Plan:');
        $this->table(
            ['File', 'School Code', 'School', 'Status'],
            collect($plan)->map(fn (array $r) => [
                basename($r['file']),
                $r['code'] ?? '—',
                $r['school']?->name ?? ($r['error'] ?: '—'),
                $r['school'] ? '✓ ready' : '✗ '.($r['error'] ?: 'no school'),
            ])->all(),
        );

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no changes made.');

            return self::SUCCESS;
        }

        $ready = collect($plan)->filter(fn (array $r) => $r['school'] !== null)->values();

        if ($ready->isEmpty()) {
            $this->error('Nothing to import — every file failed school lookup.');

            return self::FAILURE;
        }

        if (! $this->option('force')
            && ! $this->confirm("Import {$ready->count()} file(s) into the equipment table?", true)) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $results = [];
        foreach ($plan as $r) {
            if ($r['school'] === null) {
                $results[] = [basename($r['file']), $r['code'] ?? '—', '—', 0, 'skip: '.($r['error'] ?: 'no school')];

                continue;
            }

            try {
                $import = new EquipmentImport($r['school']->id);
                Excel::import($import, $r['file']);
                $results[] = [
                    basename($r['file']),
                    $r['code'],
                    $r['school']->name,
                    $import->getRowCount(),
                    '✓ ok',
                ];
            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                $first = $e->failures()[0] ?? null;
                $msg = $first
                    ? sprintf('row %d: %s', $first->row(), implode(' | ', $first->errors()))
                    : 'validation failed';
                $results[] = [basename($r['file']), $r['code'], $r['school']->name, 0, '✗ '.$msg];
            } catch (\Throwable $e) {
                $results[] = [basename($r['file']), $r['code'], $r['school']->name, 0, '✗ '.$e->getMessage()];
            }
        }

        $this->newLine();
        $this->info('Results:');
        $this->table(['File', 'Code', 'School', 'Imported', 'Status'], $results);

        $totalImported = array_sum(array_column($results, 3));
        $errors = collect($results)->filter(fn ($r) => str_starts_with($r[4], '✗'))->count();

        $this->newLine();
        $this->info("Total rows imported: {$totalImported}");
        if ($errors > 0) {
            $this->warn("Files with errors:   {$errors}");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function collectFiles(string $directory): array
    {
        $finder = (new Finder())
            ->files()
            ->in($directory)
            ->depth(0)
            ->name(['*.xlsx', '*.xls', '*.csv']);

        return collect(iterator_to_array($finder, false))
            ->map(fn (\SplFileInfo $f) => $f->getPathname())
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array{file: string, code: ?string, school: ?School, error: ?string}
     */
    private function resolveFile(string $file): array
    {
        $code = $this->option('school') ?: pathinfo($file, PATHINFO_FILENAME);
        $code = trim((string) $code);

        if ($code === '') {
            return ['file' => $file, 'code' => null, 'school' => null, 'error' => 'empty filename'];
        }

        $school = School::where('school_code', $code)
            ->orWhere('name', 'like', '%('.$code.')%')
            ->orWhere('name', 'like', '%'.$code.'%')
            ->first();

        if (! $school) {
            return ['file' => $file, 'code' => $code, 'school' => null, 'error' => 'no school matches'];
        }

        return ['file' => $file, 'code' => $code, 'school' => $school, 'error' => null];
    }
}
