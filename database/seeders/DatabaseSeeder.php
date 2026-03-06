<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentAssignment;
use App\Models\Document;
use App\Models\Ticket;
use App\Models\InternetConnection;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Super Admin user
        User::factory()->create([
            'name'     => 'System Administrator',
            'email'    => 'admin@deped.gov.ph',
            'password' => Hash::make('P@ssw0rd123'),
        ]);

        // ── Schools
        $schools = [
            ['name' => 'Davao City National High School', 'school_code' => 'SDO-DVC-001', 'district' => 'District I', 'city_municipality' => 'Davao City', 'province' => 'Davao del Sur', 'governance_level' => 'School', 'head_name' => 'Dr. Maria G. Santos', 'email' => 'dcnhs@deped.gov.ph', 'mobile_1' => '09171234567', 'latitude' => 7.0707, 'longitude' => 125.6087],
            ['name' => 'Mintal National High School', 'school_code' => 'SDO-DVC-002', 'district' => 'District II', 'city_municipality' => 'Davao City', 'province' => 'Davao del Sur', 'governance_level' => 'School', 'head_name' => 'Dr. Juan P. dela Cruz', 'email' => 'mnhs@deped.gov.ph', 'mobile_1' => '09182345678', 'latitude' => 7.1010, 'longitude' => 125.5540],
            ['name' => 'Tugbok District Science School', 'school_code' => 'SDO-DVC-003', 'district' => 'District III', 'city_municipality' => 'Davao City', 'province' => 'Davao del Sur', 'governance_level' => 'School', 'head_name' => 'Mrs. Ana C. Reyes', 'email' => 'tdss@deped.gov.ph', 'mobile_1' => '09193456789', 'latitude' => 7.1234, 'longitude' => 125.5900],
            ['name' => 'Paquibato Elementary School', 'school_code' => 'SDO-DVC-004', 'district' => 'District IV', 'city_municipality' => 'Davao City', 'province' => 'Davao del Sur', 'governance_level' => 'School', 'head_name' => 'Mr. Pedro A. Lopez', 'email' => 'pes@deped.gov.ph', 'mobile_1' => '09204567890', 'latitude' => 7.2100, 'longitude' => 125.6100],
        ];

        $schoolModels = collect($schools)->map(fn ($s) => School::create(array_merge($s, ['status' => 'active', 'region' => 'Region XI', 'division' => 'Davao City Division'])));

        // ── Employees
        $employeeData = [
            [$schoolModels[0]->id, 'EMP-2018-001', 'Carlo', 'Reyes', 'B', 'ICT Coordinator', 'ICT Department', 'non-teaching'],
            [$schoolModels[0]->id, 'EMP-2019-014', 'Jenny', 'Santos', 'C', 'Teacher III', 'Computer Science', 'teaching'],
            [$schoolModels[0]->id, 'EMP-2015-003', 'Romulo', 'Dela Cruz', 'A', 'School Technician', 'ICT Department', 'non-teaching'],
            [$schoolModels[1]->id, 'EMP-2017-007', 'Mark', 'Villanueva', 'D', 'Network Administrator', 'ICT Department', 'non-teaching'],
            [$schoolModels[1]->id, 'EMP-2020-022', 'Ana', 'Cruz', 'M', 'Teacher II', 'TLE', 'teaching'],
            [$schoolModels[2]->id, 'EMP-2016-003', 'Roberto', 'Garcia', 'P', 'School Technician', 'Maintenance', 'non-teaching'],
            [$schoolModels[2]->id, 'EMP-2021-018', 'Liza', 'Fernandez', 'S', 'Teacher III', 'Computer Science', 'teaching'],
            [$schoolModels[3]->id, 'EMP-2019-031', 'Jose', 'Manalo', 'R', 'Head Teacher', 'Administration', 'non-teaching'],
        ];

        $employees = collect($employeeData)->map(fn ($e) => Employee::create([
            'school_id'       => $e[0],
            'employee_number' => $e[1],
            'first_name'      => $e[2],
            'last_name'       => $e[3],
            'middle_name'     => $e[4],
            'position'        => $e[5],
            'department'      => $e[6],
            'employment_type' => $e[7],
            'status'          => 'active',
            'date_hired'      => now()->subYears(rand(1,8))->subDays(rand(0,300)),
            'email'           => strtolower($e[2][0] . $e[3]) . '@deped.gov.ph',
        ]));

        // ── Equipment
        $equipmentData = [
            [$schoolModels[0]->id, 'PAR-2022-001', 'SN-HP-001-22',    'HP',     'EliteBook 840 G8',  'Laptop',          'High-Value', 75000,  '2022-03-15', true,  'Batch 3', 2022, true,  'Good'],
            [$schoolModels[0]->id, 'PAR-2022-002', 'SN-DELL-002-22',  'Dell',   'Optiplex 3080',     'Desktop',         'High-Value', 55000,  '2022-03-15', true,  'Batch 3', 2022, true,  'Good'],
            [$schoolModels[0]->id, 'ICS-2021-015', 'SN-CISCO-015-21', 'Cisco',  'Catalyst 2960',     'Network Switch',  'High-Value', 85000,  '2021-06-20', false, null,       null, true,  'Fair'],
            [$schoolModels[0]->id, 'ICS-2023-021', 'SN-EPSON-021-23', 'Epson',  'L3150',             'Printer',         'Low-Value',  12000,  '2023-01-10', false, null,       null, true,  'Good'],
            [$schoolModels[1]->id, 'PAR-2023-005', 'SN-LEN-005-23',   'Lenovo', 'IdeaPad 5',         'Laptop',          'High-Value', 65000,  '2023-01-10', true,  'Batch 4', 2023, false, 'Good'],
            [$schoolModels[1]->id, 'ICS-2020-009', 'SN-EPSON-009-20', 'Epson',  'L6170',             'Printer',         'Low-Value',  18000,  '2020-09-01', false, null,       null, false, 'Poor'],
            [$schoolModels[2]->id, 'PAR-2022-018', 'SN-ACER-018-22',  'Acer',   'Aspire 5',          'Laptop',          'Low-Value',  48000,  '2022-07-15', false, null,       null, true,  'Good'],
            [$schoolModels[2]->id, 'PAR-2022-019', 'SN-ASUS-019-22',  'Asus',   'VivoBook 15',       'Laptop',          'Low-Value',  46000,  '2022-07-15', true,  'Batch 3', 2022, true,  'Good'],
            [$schoolModels[3]->id, 'ICS-2019-003', 'SN-HP-003-19',    'HP',     'LaserJet Pro M404n','Printer',         'High-Value', 52000,  '2019-11-20', false, null,       null, true,  'Fair'],
        ];

        $equipmentModels = collect($equipmentData)->map(fn ($e) => Equipment::create([
            'school_id'            => $e[0],
            'property_no'          => $e[1],
            'serial_number'        => $e[2],
            'brand'                => $e[3],
            'model'                => $e[4],
            'equipment_type'       => $e[5],
            'item_type'            => 'Equipment',
            'category'             => $e[6],
            'acquisition_cost'     => $e[7],
            'acquisition_date'     => $e[8],
            'is_dcp'               => $e[9],
            'dcp_package'          => $e[10],
            'dcp_year'             => $e[11],
            'is_functional'        => $e[12],
            'condition'            => $e[13],
            'accountability_status'=> 'unassigned',
            'mode_of_acquisition'  => 'Purchased',
            'source_of_acquisition'=> 'Central Office',
            'source_of_funds'      => 'General Fund',
            'under_warranty'       => now()->lt(now()->parse($e[8])->addYears(3)),
            'warranty_end_date'    => now()->parse($e[8])->addYears(3),
            'specifications'       => "Brand: {$e[3]} | Model: {$e[4]} | Type: {$e[5]}",
        ]));

        // ── Assignments
        $assignments = [
            [$equipmentModels[0]->id, $employees[0]->id, '2022-03-20', 'ICT Office use'],
            [$equipmentModels[1]->id, $employees[1]->id, '2022-03-20', 'Computer Lab instruction'],
            [$equipmentModels[2]->id, $employees[0]->id, '2021-06-25', 'Network room management'],
            [$equipmentModels[3]->id, $employees[2]->id, '2023-01-15', 'Admin printing'],
            [$equipmentModels[5]->id, $employees[3]->id, '2020-09-05', 'Office use – poor condition'],
            [$equipmentModels[6]->id, $employees[5]->id, '2022-07-20', 'Library use'],
            [$equipmentModels[7]->id, $employees[6]->id, '2022-07-20', 'Classroom instruction'],
            [$equipmentModels[8]->id, $employees[7]->id, '2019-11-25', "Principal's office"],
        ];

        foreach ($assignments as $a) {
            EquipmentAssignment::create([
                'equipment_id'     => $a[0],
                'employee_id'      => $a[1],
                'assigned_at'      => $a[2],
                'returned_at'      => null,
                'assigned_by'      => 'System Seeder',
                'transaction_type' => 'Beginning Inventory',
                'supporting_doc_type' => 'PAR',
                'notes'            => $a[3],
                'is_active'        => true,
            ]);
            Equipment::find($a[0])?->update(['accountability_status' => 'assigned']);
        }

        // Mark 1 for disposal
        $equipmentModels[5]->update(['accountability_status' => 'For Disposal', 'is_functional' => false, 'condition' => 'Poor']);

        // ── Internet Connections
        $connections = [
            [$schoolModels[0]->id, 'PLDT', 'ACC-PLDT-001', 'FiberX 100', 100, 50, 95.4, 49.8, 12],
            [$schoolModels[1]->id, 'Globe', 'ACC-GLOBE-012', 'GFiber 50', 50, 25, 48.2, 24.1, 18],
            [$schoolModels[2]->id, 'Converge', 'ACC-CONV-007', 'FiberX 25', 25, 12, 22.7, 11.3, 25],
            [$schoolModels[3]->id, 'Smart', 'ACC-SMART-018', 'LTE Prepaid', 10, 5, 7.3, 3.1, 80],
        ];

        foreach ($connections as $c) {
            InternetConnection::create([
                'school_id'                 => $c[0],
                'isp'                       => $c[1],
                'account_number'            => $c[2],
                'plan_name'                 => $c[3],
                'contracted_download_speed' => $c[4],
                'contracted_upload_speed'   => $c[5],
                'actual_download_speed'     => $c[6],
                'actual_upload_speed'       => $c[7],
                'latency_ms'                => $c[8],
                'connection_type'           => str_contains($c[1], 'Smart') ? 'LTE' : 'Fiber',
                'status'                    => 'active',
                'speed_test_date'           => now()->subDays(rand(1, 30)),
            ]);
        }

        // ── Tickets
        $ticketData = [
            [$schoolModels[0]->id, $equipmentModels[2]->id, $employees[0]->id, 'Network Switch Intermittent', 'Switch port 12 drops connection every few hours. Affects Computer Lab 1.', 'high', 'open', $employees[0]->id],
            [$schoolModels[1]->id, $equipmentModels[4]->id, $employees[3]->id, 'Laptop Slow Boot', 'Lenovo IdeaPad 5 takes 5+ minutes to boot. Suspected HDD issue.', 'medium', 'in-progress', $employees[3]->id],
            [$schoolModels[0]->id, $equipmentModels[1]->id, $employees[1]->id, 'Monitor flickering', 'External monitor flickers when brightness above 80%.', 'low', 'resolved', $employees[2]->id],
            [$schoolModels[2]->id, $equipmentModels[6]->id, $employees[5]->id, 'Laptop battery draining fast', 'Acer Aspire 5 battery drains in under 1 hour. Was fully charged before.', 'medium', 'open', $employees[5]->id],
        ];

        foreach ($ticketData as $t) {
            Ticket::create([
                'school_id'    => $t[0],
                'equipment_id' => $t[1],
                'reporter_id'  => $t[2],
                'issue_title'  => $t[3],
                'description'  => $t[4],
                'priority'     => $t[5],
                'status'       => $t[6],
                'assigned_to_id'=> $t[7]->id,
                'resolved_at'  => $t[6] === 'resolved' ? now()->subDays(3) : null,
            ]);
        }

        // ── Documents
        $docData = [
            [$schoolModels[0]->id, $equipmentModels[0]->id, 'PAR', 'PAR-2022-001', 'PAR for HP EliteBook 840 G8', 'Property Acknowledgment Receipt for HP EliteBook 840 G8 assigned to ICT Coordinator.', '2022-03-22'],
            [$schoolModels[0]->id, $equipmentModels[1]->id, 'PAR', 'PAR-2022-002', 'PAR for Dell Optiplex 3080', 'Property Acknowledgment Receipt for Dell Desktop assigned to Computer Lab.', '2022-03-22'],
            [$schoolModels[0]->id, null, 'IAR', 'IAR-2022-001', 'IAR – DCP Batch 3 Delivery', 'Inspection and Acceptance Report for Batch 3 DCP delivery. All units passed inspection.', '2022-03-18'],
            [$schoolModels[1]->id, $equipmentModels[5]->id, 'WMR', 'WMR-2023-001', 'WMR – Epson L6170 Disposal', 'Waste Material Report for non-functional Epson L6170 printer.', '2023-10-02'],
            [$schoolModels[2]->id, $equipmentModels[6]->id, 'ICS', 'ICS-2022-018', 'ICS for Acer Aspire 5', 'Inventory Custodian Slip for Acer Aspire 5 assigned to school library.', '2022-07-21'],
        ];

        foreach ($docData as $d) {
            Document::create([
                'school_id'     => $d[0],
                'equipment_id'  => $d[1] ? $d[1]->id : null,
                'document_type' => $d[2],
                'document_no'   => $d[3],
                'title'         => $d[4],
                'description'   => $d[5],
                'file_path'     => 'documents/sample.pdf',
                'file_name'     => strtolower(str_replace(' ', '_', $d[4])) . '.pdf',
                'document_date' => $d[6],
                'mime_type'     => 'application/pdf',
            ]);
        }

        $this->command->info('✅ IEEPIS seeded successfully!');
        $this->command->info('   Admin: admin@deped.gov.ph / P@ssw0rd123');
        $this->command->info("   Schools: {$schoolModels->count()} | Employees: {$employees->count()} | Equipment: {$equipmentModels->count()}");
    }
}
