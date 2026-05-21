<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Division;
use App\Models\District;
use App\Models\School;
use App\Models\Employee;
use App\Models\Equipment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DcpDistributionSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        try {
            // 1. Division
            $division = Division::updateOrCreate(
                ['name' => 'Dapitan City'],
                [
                    'code' => 'DAP-' . strtoupper(Str::random(4)),
                    'region' => 'Region IX',
                ]
            );

            // 2. Districts
            $districts = ['Baylimango', 'Barcelona', 'Dapitan', 'Sulangon', 'Potungan'];

            foreach ($districts as $districtName) {
                $district = District::updateOrCreate(
                    ['name' => $districtName],
                    [
                        'division_id' => $division->id,
                        'division' => $division->name,
                        'code' => strtoupper(substr($districtName, 0, 3)) . rand(100, 999),
                        'region' => 'Region IX',
                    ]
                );

                // 3. School
                $school = School::updateOrCreate(
                    ['school_code' => 'DCP-SCH-' . strtoupper(Str::random(6))],
                    [
                        'name' => "{$districtName} DCP Center",
                        'district_id' => $district->id,
                        'status' => 'active',
                        'governance_level' => 'School',
                        'division' => $division->name,
                        'district' => $district->name,
                    ]
                );

                // 4. Employees
                for ($i = 0; $i < 5; $i++) {
                    $fName = ['Ren', 'Jan', 'Ana', 'Kim', 'Leo'][rand(0, 4)];
                    $lName = ['Cruz', 'Lim', 'Tan', 'Go', 'Sy'][rand(0, 4)];
                    Employee::create([
                        'school_id' => $school->id,
                        'employee_number' => 'EMP-' . strtoupper(Str::random(8)),
                        'first_name' => $fName,
                        'last_name' => $lName,
                        'position' => 'ICT Coordinator',
                        'email' => 'dummy' . rand(1, 999999) . '@example.com',
                        'status' => 'active',
                        'employment_type' => 'non-teaching',
                    ]);
                }

                // 5. Equipment
                $types = ['Laptop', 'Desktop', 'TV', 'Router'];
                foreach ($types as $type) {
                    for ($j = 0; $j < rand(10, 15); $j++) {
                        $isNonTeaching = $type === 'Laptop' && rand(0, 1) === 1;
                        Equipment::create([
                            'school_id' => $school->id,
                            'property_no' => 'PROP-' . strtoupper(Str::random(12)),
                            'serial_number' => 'SER-' . strtoupper(Str::random(12)),
                            'equipment_type' => $type,
                            'item_type' => $type,
                            'is_dcp' => true,
                            'dcp_package' => 'Batch 2024',
                            'accountability_status' => ['Distributed', 'Pending', 'Received'][rand(0, 2)],
                            'is_functional' => true,
                            'condition' => 'Good',
                            'brand' => 'DCP Brand',
                            'model' => 'Model-2024',
                            'remarks' => $isNonTeaching ? 'Non-Teaching' : 'Teaching',
                        ]);
                    }
                }
            }

            DB::commit();
            $this->command->info('DCP Seeding completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error during seeding: ' . $e->getMessage());
            throw $e;
        }
    }
}
