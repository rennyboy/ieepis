<?php

namespace Tests\Feature;

use App\Enums\AccountabilityStatus;
use App\Imports\EquipmentImport;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentAssignment;
use App\Models\School;
use App\Scopes\SchoolScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentImportDepEdFormatTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchool(): School
    {
        return School::create([
            'name' => 'Dapitan City National High School',
            'school_code' => 'DCNHS-303880',
        ]);
    }

    private function makeEmployee(School $school, string $last, string $first): Employee
    {
        return Employee::create([
            'school_id' => $school->id,
            'employee_number' => 'AUTO-'.strtoupper(substr(md5($last.$first), 0, 8)),
            'first_name' => $first,
            'last_name' => $last,
            'position' => 'Teacher I',
        ]);
    }

    public function test_deped_format_row_sets_officer_and_custodian(): void
    {
        $school = $this->makeSchool();
        $officer = $this->makeEmployee($school, 'ONGANIZA', 'JUNRY');
        $custodian = $this->makeEmployee($school, 'BAIT-IT', 'REGIE');

        $row = [
            'no.' => 1,
            'property_no' => 'DCP-303880-2023-DSA580-0001',
            'serial_number' => 'XPN-DSA5850-246HV121-00813X',
            'item' => 'Smart TV',
            'brand_manufacturer' => 'Xitrix',
            'model' => 'SMART TV 58" LED',
            'accountable_officer' => 'JUNRY B. ONGANIZA',
            'date_assigned_accountable_officer' => '2024-10-27',
            'end_user' => 'REGIE E. BAIT-IT',
            'date_assigned_end_user' => '2024-10-28',
            'transaction_type' => 'Delivery',
            'erquipment_condition' => 'Serviceable',
        ];

        (new EquipmentImport($school->id))->model($row);

        $equipment = Equipment::where('property_no', $row['property_no'])->firstOrFail();

        $this->assertSame($officer->id, $equipment->accountable_officer_id);
        $this->assertSame(AccountabilityStatus::Assigned, $equipment->accountability_status);

        $assignment = EquipmentAssignment::withoutGlobalScope(SchoolScope::class)
            ->where('equipment_id', $equipment->id)
            ->whereNull('returned_at')
            ->firstOrFail();

        $this->assertSame($custodian->id, $assignment->custodian_id);
        $this->assertSame('2024-10-28', $assignment->assigned_at->format('Y-m-d'));
    }

    public function test_officer_only_row_sets_officer_and_leaves_status_unassigned(): void
    {
        $school = $this->makeSchool();
        $officer = $this->makeEmployee($school, 'NICDAO', 'ERLINE');

        $row = [
            'property_no' => 'DCP-303880-2023-DSA580-0002',
            'item' => 'Laptop',
            'accountable_officer' => 'ERLINE B. NICDAO',
        ];

        (new EquipmentImport($school->id))->model($row);

        $equipment = Equipment::where('property_no', $row['property_no'])->firstOrFail();

        $this->assertSame($officer->id, $equipment->accountable_officer_id);
        $this->assertSame(AccountabilityStatus::Unassigned, $equipment->accountability_status);

        $hasAssignment = EquipmentAssignment::withoutGlobalScope(SchoolScope::class)
            ->where('equipment_id', $equipment->id)
            ->exists();
        $this->assertFalse($hasAssignment, 'No EquipmentAssignment should be created when only the officer is present.');
    }

    public function test_resolves_employee_when_middle_initial_is_two_letters(): void
    {
        $school = $this->makeSchool();
        $officer = $this->makeEmployee($school, 'GAHISAN', 'DOROTHY');

        $row = [
            'property_no' => 'DCP-303880-2023-DSA580-0003',
            'item' => 'Monitor',
            'accountable_officer' => 'MA. DOROTHY JOY GAHISAN',
        ];

        (new EquipmentImport($school->id))->model($row);

        $equipment = Equipment::where('property_no', $row['property_no'])->firstOrFail();
        $this->assertSame($officer->id, $equipment->accountable_officer_id);
    }

    public function test_resolution_summary_counts_resolved_and_collects_unresolved_uniquely(): void
    {
        $school = $this->makeSchool();
        $this->makeEmployee($school, 'ONGANIZA', 'JUNRY');
        $this->makeEmployee($school, 'BAIT-IT', 'REGIE');

        $import = new EquipmentImport($school->id);

        $rows = [
            // Row 1: both resolve.
            [
                'property_no' => 'P-1',
                'accountable_officer' => 'JUNRY B. ONGANIZA',
                'end_user' => 'REGIE E. BAIT-IT',
            ],
            // Row 2: officer resolves, custodian doesn't (no such employee).
            [
                'property_no' => 'P-2',
                'accountable_officer' => 'JUNRY B. ONGANIZA',
                'end_user' => 'UNKNOWN E. PERSON',
            ],
            // Row 3: officer name unresolved (same unknown — should be deduped).
            [
                'property_no' => 'P-3',
                'accountable_officer' => 'UNKNOWN E. PERSON',
            ],
            // Row 4: another distinct unresolved officer.
            [
                'property_no' => 'P-4',
                'accountable_officer' => 'NOBODY X. HERE',
            ],
        ];

        foreach ($rows as $row) {
            $import->model($row);
        }

        $summary = $import->getResolutionSummary();

        $this->assertSame(4, $summary['rows']);
        $this->assertSame(2, $summary['officer_resolved'], 'Officer should resolve twice (rows 1 + 2).');
        $this->assertSame(1, $summary['custodian_resolved'], 'Custodian should resolve once (row 1 only).');

        // Deduplication: "UNKNOWN E. PERSON" appears as both unresolved officer (row 3)
        // and unresolved custodian (row 2). Each list dedupes independently.
        sort($summary['officer_unresolved']);
        $this->assertSame(['NOBODY X. HERE', 'UNKNOWN E. PERSON'], $summary['officer_unresolved']);
        $this->assertSame(['UNKNOWN E. PERSON'], $summary['custodian_unresolved']);
    }
}
