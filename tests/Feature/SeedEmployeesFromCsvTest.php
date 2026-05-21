<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\School;
use App\Scopes\SchoolScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SeedEmployeesFromCsvTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchools(): array
    {
        return [
            'a' => School::create([
                'name' => 'Dapitan City National High School',
                'school_code' => '303880',
            ]),
            'b' => School::create([
                'name' => 'Selinog Integrated School',
                'school_code' => '125939',
            ]),
        ];
    }

    private function writeCsv(string $body): string
    {
        $path = tempnam(sys_get_temp_dir(), 'roster_').'.csv';
        file_put_contents($path, $body);

        return $path;
    }

    public function test_creates_employees_for_matched_schools_and_skips_unmatched(): void
    {
        $schools = $this->makeSchools();

        $csv = <<<CSV
,,,
,NAME,POSITION/SG,SCHOOL ASSIGNMENT
1,"LEAR, ALOHA ROCAMORA",ASSISTANT SCHOOL PRINCIPAL II - 19,Dapitan City National High School
2,"ONGANIZA, SHENNA MAY AZOTE",TEACHER I - 11,Selinog Integrated School
3,"FAKE, NAME UNKNOWN",TEACHER I - 11,No Such School That Exists
CSV;

        $path = $this->writeCsv($csv);

        Artisan::call('employees:seed-from-csv', ['path' => $path, '--force' => true]);

        $this->assertSame(2, Employee::withoutGlobalScope(SchoolScope::class)->count());

        $lear = Employee::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $schools['a']->id)
            ->whereRaw('LOWER(last_name) = ?', ['lear'])
            ->first();

        $this->assertNotNull($lear);
        $this->assertSame('ALOHA', $lear->first_name);
        $this->assertSame('ROCAMORA', $lear->middle_name);
        $this->assertSame('ASSISTANT SCHOOL PRINCIPAL II', $lear->position);

        $shenna = Employee::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $schools['b']->id)
            ->whereRaw('LOWER(last_name) = ?', ['onganiza'])
            ->first();

        $this->assertNotNull($shenna);
        $this->assertSame('SHENNA', $shenna->first_name);
        $this->assertSame('MAY AZOTE', $shenna->middle_name);

        @unlink($path);
    }

    public function test_school_filter_narrows_scope_to_one_school(): void
    {
        $schools = $this->makeSchools();

        $csv = <<<CSV
,,,
,NAME,POSITION/SG,SCHOOL ASSIGNMENT
1,"LEAR, ALOHA ROCAMORA",ASSISTANT SCHOOL PRINCIPAL II - 19,Dapitan City National High School
2,"ONGANIZA, SHENNA MAY AZOTE",TEACHER I - 11,Selinog Integrated School
CSV;

        $path = $this->writeCsv($csv);

        Artisan::call('employees:seed-from-csv', [
            'path' => $path,
            '--school' => '303880',
            '--force' => true,
        ]);

        $this->assertSame(1, Employee::withoutGlobalScope(SchoolScope::class)->count());
        $this->assertTrue(
            Employee::withoutGlobalScope(SchoolScope::class)
                ->where('school_id', $schools['a']->id)
                ->exists(),
        );
        $this->assertFalse(
            Employee::withoutGlobalScope(SchoolScope::class)
                ->where('school_id', $schools['b']->id)
                ->exists(),
        );

        @unlink($path);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->makeSchools();

        $csv = <<<CSV
,,,
,NAME,POSITION/SG,SCHOOL ASSIGNMENT
1,"LEAR, ALOHA ROCAMORA",ASSISTANT SCHOOL PRINCIPAL II - 19,Dapitan City National High School
CSV;

        $path = $this->writeCsv($csv);

        Artisan::call('employees:seed-from-csv', [
            'path' => $path,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, Employee::withoutGlobalScope(SchoolScope::class)->count());

        @unlink($path);
    }

    public function test_rerun_updates_in_place_without_duplicates(): void
    {
        $schools = $this->makeSchools();

        $csv = <<<CSV
,,,
,NAME,POSITION/SG,SCHOOL ASSIGNMENT
1,"LEAR, ALOHA ROCAMORA",TEACHER I - 11,Dapitan City National High School
CSV;

        $path = $this->writeCsv($csv);

        Artisan::call('employees:seed-from-csv', ['path' => $path, '--force' => true]);
        $this->assertSame(1, Employee::withoutGlobalScope(SchoolScope::class)->count());

        // Same row, different position — should update, not create.
        $csv2 = <<<CSV
,,,
,NAME,POSITION/SG,SCHOOL ASSIGNMENT
1,"LEAR, ALOHA ROCAMORA",ASSISTANT SCHOOL PRINCIPAL II - 19,Dapitan City National High School
CSV;
        $path2 = $this->writeCsv($csv2);

        Artisan::call('employees:seed-from-csv', ['path' => $path2, '--force' => true]);

        $this->assertSame(1, Employee::withoutGlobalScope(SchoolScope::class)->count());
        $this->assertSame(
            'ASSISTANT SCHOOL PRINCIPAL II',
            Employee::withoutGlobalScope(SchoolScope::class)->first()->position,
        );

        @unlink($path);
        @unlink($path2);
    }
}
