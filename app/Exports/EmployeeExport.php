<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Employee::with(['school'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee Number',
            'First Name',
            'Last Name',
            'Middle Name',
            'Suffix',
            'Position',
            'Department',
            'RO Office',
            'SDO Office',
            'Employment Type',
            'Status',
            'School',
            'Email',
            'Personal Email',
            'Mobile 1',
            'Mobile 2',
            'Date Hired',
            'Is OIC',
            'OIC Office',
            'Is Non-DepEd Funded',
            'Is Inactive',
            'Date of Separation',
            'Cause of Separation',
            'Detailed From',
            'Detailed To',
            'Created At',
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->employee_number,
            $employee->first_name,
            $employee->last_name,
            $employee->middle_name,
            $employee->suffix,
            $employee->position,
            $employee->department,
            $employee->ro_office,
            $employee->sdo_office,
            $employee->employment_type,
            $employee->status,
            $employee->school?->name,
            $employee->email,
            $employee->personal_email,
            $employee->mobile_1,
            $employee->mobile_2,
            $employee->date_hired?->format('Y-m-d'),
            $employee->is_oic ? 'Yes' : 'No',
            $employee->oic_office,
            $employee->is_non_deped_funded ? 'Yes' : 'No',
            $employee->is_inactive ? 'Yes' : 'No',
            $employee->date_of_separation?->format('Y-m-d'),
            $employee->cause_of_separation,
            $employee->detailed_from,
            $employee->detailed_to,
            $employee->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}