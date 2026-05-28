<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeExport;
use App\Imports\EmployeeImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeExcelController extends Controller
{
    public function export()
    {
        $filename = 'employees_export_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new EmployeeExport, $filename);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new EmployeeImport;
            Excel::import($import, $request->file('file'));

            return back()->with('success', 'Employees imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function template()
    {
        $filename = 'employee_import_template.xlsx';

        return Excel::download(new class implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function collection()
            {
                return collect([[null]]);
            }

            public function headings(): array
            {
                return [
                    'employee_number',
                    'first_name',
                    'last_name',
                    'middle_name',
                    'suffix',
                    'position',
                    'department',
                    'ro_office',
                    'sdo_office',
                    'employment_type',
                    'status',
                    'school',
                    'email',
                    'personal_email',
                    'mobile_1',
                    'mobile_2',
                    'date_hired',
                    'is_oic',
                    'oic_office',
                    'is_non_deped_funded',
                    'is_inactive',
                    'date_of_separation',
                    'cause_of_separation',
                    'detailed_from',
                    'detailed_to',
                ];
            }
        }, $filename);
    }
}