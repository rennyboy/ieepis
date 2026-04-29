<?php

namespace App\Exports;

use App\Models\Equipment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EquipmentExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Equipment::with(['school', 'activeAssignment.employee'])
            ->orderBy('property_no')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Property No.',
            'Old Property No.',
            'Serial Number',
            'Equipment Type',
            'Brand',
            'Model',
            'Specifications',
            'Category',
            'Classification',
            'Is DCP',
            'DCP Package',
            'DCP Year',
            'Condition',
            'Is Functional',
            'Accountability Status',
            'School',
            'Current Assignee',
            'Acquisition Cost',
            'Acquisition Date',
            'Mode of Acquisition',
            'Source of Acquisition',
            'Supplier',
            'Warranty End Date',
            'Equipment Location',
            'Remarks',
            'Created At',
        ];
    }

    public function map($equipment): array
    {
        return [
            $equipment->property_no,
            $equipment->old_property_no,
            $equipment->serial_number,
            $equipment->equipment_type,
            $equipment->brand,
            $equipment->model,
            $equipment->specifications,
            $equipment->category,
            $equipment->classification,
            $equipment->is_dcp ? 'Yes' : 'No',
            $equipment->dcp_package,
            $equipment->dcp_year,
            $equipment->condition,
            $equipment->is_functional ? 'Yes' : 'No',
            $equipment->accountability_status,
            $equipment->school?->name,
            $equipment->activeAssignment?->employee?->full_name,
            $equipment->acquisition_cost,
            $equipment->acquisition_date?->format('Y-m-d'),
            $equipment->mode_of_acquisition,
            $equipment->source_of_acquisition,
            $equipment->supplier,
            $equipment->warranty_end_date?->format('Y-m-d'),
            $equipment->equipment_location,
            $equipment->remarks,
            $equipment->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}