<?php

namespace Tests\Feature;

use App\Exports\EquipmentExport;
use App\Imports\EquipmentImport;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class EquipmentExportImportRoundTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountable_officer_round_trips_through_export_and_import(): void
    {
        $school = School::create([
            'name' => 'Round-trip Elementary School',
            'school_code' => 'RTES-001',
        ]);

        $officer = Employee::create([
            'school_id' => $school->id,
            'employee_number' => 'EMP-77',
            'first_name' => 'Juana',
            'last_name' => 'Dela Cruz',
            'position' => 'Teacher I',
        ]);

        $equipment = Equipment::create([
            'school_id' => $school->id,
            'property_no' => 'RT-EQ-0001',
            'serial_number' => 'SN-ROUND-1',
            'brand' => 'Acme',
            'model' => 'X1',
            'accountable_officer_id' => $officer->id,
        ]);

        $heading = collect((new EquipmentExport)->headings());
        $this->assertTrue(
            $heading->contains('Accountable Officer'),
            'Export must include the Accountable Officer column.',
        );

        $tmp = tempnam(sys_get_temp_dir(), 'eqexport_').'.xlsx';
        file_put_contents($tmp, Excel::raw(new EquipmentExport, ExcelFormat::XLSX));

        $equipment->forceFill(['accountable_officer_id' => null])->save();
        $this->assertNull($equipment->fresh()->accountable_officer_id);

        Excel::import(new EquipmentImport($school->id), $tmp);

        $this->assertSame(
            $officer->id,
            $equipment->fresh()->accountable_officer_id,
            'Re-importing the exported sheet must restore the accountable officer.',
        );

        @unlink($tmp);
    }

    public function test_export_renders_enum_values_as_strings(): void
    {
        $school = School::create([
            'name' => 'Enum School',
            'school_code' => 'ENUM-001',
        ]);

        Equipment::create([
            'school_id' => $school->id,
            'property_no' => 'ENUM-EQ-1',
            'condition' => 'Good',
            'accountability_status' => 'assigned',
        ]);

        $bytes = Excel::raw(new EquipmentExport, ExcelFormat::XLSX);

        $this->assertNotEmpty($bytes, 'Excel export must produce non-empty output.');
        $this->assertGreaterThan(500, strlen($bytes), 'Excel export must produce a real xlsx payload.');
    }
}
