<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\School;
use App\Scopes\SchoolScope;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedEmployeesFromCsv extends Command
{
    protected $signature = 'employees:seed-from-csv
        {path? : CSV path (defaults to database/seeders/LIST_OF_EMPLOYEES_03_09.csv)}
        {--school= : Filter by school_code or fuzzy name; only matching rows are seeded}
        {--dry-run : Show the plan, write nothing}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Upsert Employee records from a DepEd division roster CSV. Idempotent.';

    public function handle(): int
    {
        $path = (string) ($this->argument('path') ?: base_path('database/seeders/LIST_OF_EMPLOYEES_03_09.csv'));

        if (! is_file($path)) {
            $this->error("CSV not found: {$path}");

            return self::FAILURE;
        }

        $rows = $this->readRows($path);

        if ($rows === []) {
            $this->warn('No data rows found in CSV.');

            return self::SUCCESS;
        }

        $schoolFilter = (string) ($this->option('school') ?? '');
        $isDryRun = (bool) $this->option('dry-run');

        $plan = $this->plan($rows, $schoolFilter);

        $this->renderPlan($plan, $isDryRun);

        if ($isDryRun) {
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Proceed with seeding?', true)) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $tally = $this->apply($plan);

        $this->newLine();
        $this->info(sprintf(
            'Done. %d created, %d updated, %d skipped.',
            $tally['created'],
            $tally['updated'],
            $tally['skipped'],
        ));

        return self::SUCCESS;
    }

    /**
     * @return list<array{name:string, position:string, assignment:string}>
     */
    private function readRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $rows = [];

        while (($cells = fgetcsv($handle)) !== false) {
            // Skip blank rows and the two header lines (the file starts with
            // ",,," then ",NAME,POSITION/SG,SCHOOL ASSIGNMENT").
            if (count($cells) < 4) {
                continue;
            }

            [$_num, $name, $position, $assignment] = array_pad($cells, 4, '');

            $name = trim((string) $name);
            $position = trim((string) $position);
            $assignment = trim((string) $assignment);

            if ($name === '' || strtoupper($name) === 'NAME') {
                continue;
            }

            $rows[] = compact('name', 'position', 'assignment');
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  list<array{name:string, position:string, assignment:string}>  $rows
     * @return list<array{name:string, position:string, assignment:string, school:?School, parsed:array, reason:?string}>
     */
    private function plan(array $rows, string $schoolFilter): array
    {
        $schools = School::withoutGlobalScope(SchoolScope::class)->get();
        $schoolCache = [];

        $matchesFilter = function (?School $school) use ($schoolFilter): bool {
            if ($schoolFilter === '') {
                return true;
            }
            if ($school === null) {
                return false;
            }
            $needle = Str::lower($schoolFilter);

            return Str::lower((string) $school->school_code) === $needle
                || str_contains(Str::lower((string) $school->name), $needle);
        };

        $plan = [];

        foreach ($rows as $row) {
            $school = $this->resolveSchool($row['assignment'], $schools, $schoolCache);
            $parsed = $this->parseName($row['name']);

            $reason = null;
            if ($school === null) {
                $reason = 'no school match';
            } elseif ($parsed['first_name'] === '' || $parsed['last_name'] === '') {
                $reason = 'unparseable name';
            } elseif (! $matchesFilter($school)) {
                $reason = 'filtered out';
            }

            $plan[] = $row + [
                'school' => $school,
                'parsed' => $parsed,
                'reason' => $reason,
            ];
        }

        return $plan;
    }

    private function resolveSchool(string $assignment, $schools, array &$cache): ?School
    {
        if ($assignment === '') {
            return null;
        }

        if (array_key_exists($assignment, $cache)) {
            return $cache[$assignment];
        }

        // Multi-school assignments use "/" — match on the first part only.
        $primary = trim(explode('/', $assignment)[0]);
        $needle = $this->normalizeSchoolName($primary);

        // Match strategies, in order of specificity. Avoid the dangerous
        // "assignment contains school_name" reverse — that lets a short
        // parent name like "Dapitan City" swallow rows belonging to the
        // longer "Dapitan City National High School".
        $match = $schools->first(function (School $s) use ($needle) {
            $normalized = $this->normalizeSchoolName((string) $s->name);

            return Str::lower((string) $s->school_code) === $needle
                || $normalized === $needle
                || str_contains($normalized, $needle);
        });

        return $cache[$assignment] = $match;
    }

    /**
     * Strip a trailing "(123456)" DepEd code suffix and lowercase, so a
     * stored name like "Dapitan City National High School (303880)" can be
     * compared against the bare assignment string used in the roster CSV.
     */
    private function normalizeSchoolName(string $name): string
    {
        return Str::lower(trim((string) preg_replace('/\s*\(\d+\)\s*$/', '', $name)));
    }

    /**
     * @return array{last_name:string, first_name:string, middle_name:?string}
     */
    private function parseName(string $name): array
    {
        $name = trim($name);

        if (! str_contains($name, ',')) {
            return ['last_name' => '', 'first_name' => '', 'middle_name' => null];
        }

        // Split on first comma only — some rows are "LAST, FIRST, JR. MIDDLE".
        $commaPos = strpos($name, ',');
        $last = trim(substr($name, 0, $commaPos));
        $rest = trim(substr($name, $commaPos + 1));

        if ($last === '' || $rest === '') {
            return ['last_name' => $last, 'first_name' => '', 'middle_name' => null];
        }

        $restParts = preg_split('/\s+/', $rest) ?: [];
        $first = (string) ($restParts[0] ?? '');
        $middle = trim(implode(' ', array_slice($restParts, 1))) ?: null;

        return [
            'last_name' => $last,
            'first_name' => $first,
            'middle_name' => $middle,
        ];
    }

    private function parsePosition(string $position): string
    {
        if ($position === '') {
            return 'Unspecified';
        }

        // "ADMINISTRATIVE OFFICER II - 11" → "ADMINISTRATIVE OFFICER II".
        // "ADMINISTRATIVE AIDE I - 01 (CT)" → "ADMINISTRATIVE AIDE I".
        if (preg_match('/^(.*?)\s+-\s+\d+/', $position, $m) === 1) {
            return trim($m[1]);
        }

        return $position;
    }

    private function renderPlan(array $plan, bool $isDryRun): void
    {
        $bySchool = [];
        $unmatchedAssignments = [];

        foreach ($plan as $row) {
            $label = $row['school']?->name ?? '(no school match)';
            if ($row['reason'] === 'filtered out') {
                $label = '(filtered out)';
            }
            $bySchool[$label] ??= ['ready' => 0, 'skipped' => 0];
            $bySchool[$label][$row['reason'] === null ? 'ready' : 'skipped']++;

            if ($row['reason'] === 'no school match' && $row['assignment'] !== '') {
                $unmatchedAssignments[$row['assignment']] = ($unmatchedAssignments[$row['assignment']] ?? 0) + 1;
            }
        }

        $this->newLine();
        $this->info($isDryRun ? 'Plan (dry-run, no writes):' : 'Plan:');
        $this->table(
            ['School', 'Ready', 'Skipped'],
            collect($bySchool)
                ->map(fn ($counts, $label) => [$label, $counts['ready'], $counts['skipped']])
                ->values()
                ->all(),
        );

        if ($unmatchedAssignments !== []) {
            arsort($unmatchedAssignments);
            $top = array_slice($unmatchedAssignments, 0, 10, true);

            $this->newLine();
            $this->warn('Top unmatched school assignment strings (curate the CSV or seed missing schools):');
            $this->table(
                ['Assignment String', 'Rows'],
                collect($top)->map(fn ($n, $assignment) => [$assignment, $n])->values()->all(),
            );
        }
    }

    /**
     * @return array{created:int, updated:int, skipped:int}
     */
    private function apply(array $plan): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($plan as $row) {
            if ($row['reason'] !== null) {
                $skipped++;
                continue;
            }

            /** @var School $school */
            $school = $row['school'];
            $parsed = $row['parsed'];
            $position = $this->parsePosition($row['position']);

            $employeeNumber = 'AUTO-'.strtoupper(substr(
                md5($school->id.'|'.$parsed['last_name'].'|'.$parsed['first_name']),
                0,
                10,
            ));

            $employee = Employee::withoutGlobalScope(SchoolScope::class)
                ->withTrashed()
                ->where('school_id', $school->id)
                ->whereRaw('LOWER(last_name) = ?', [Str::lower($parsed['last_name'])])
                ->whereRaw('LOWER(first_name) = ?', [Str::lower($parsed['first_name'])])
                ->first();

            if ($employee === null) {
                Employee::create([
                    'school_id' => $school->id,
                    'employee_number' => $employeeNumber,
                    'first_name' => $parsed['first_name'],
                    'last_name' => $parsed['last_name'],
                    'middle_name' => $parsed['middle_name'],
                    'position' => $position,
                ]);
                $created++;
            } else {
                $employee->forceFill([
                    'middle_name' => $parsed['middle_name'] ?? $employee->middle_name,
                    'position' => $position,
                ])->save();
                $updated++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
    }
}
