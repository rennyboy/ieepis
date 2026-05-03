<?php

namespace App\Http\Controllers;

use App\Exports\EquipmentExport;
use App\Imports\EquipmentImport;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EquipmentExcelController extends Controller
{
    public function export()
    {
        $filename = 'equipment_export_' . now()->format('Y-m-d_His') . '.xlsx';
        
        return Excel::download(new EquipmentExport, $filename);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            $import = new EquipmentImport;
            Excel::import($import, $request->file('file'));

            Notification::make()
                ->success()
                ->title('Import Successful')
                ->body('Equipment data has been imported.')
                ->send();

            return back()->with('success', 'Equipment imported successfully.');
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Import Failed')
                ->body('Error: ' . $e->getMessage())
                ->send();

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function template()
    {
        $filename = 'equipment_import_template.xlsx';
        
        // Generate a template with only headings
        return Excel::download(new class implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function collection()
            {
                return collect([[null]]);
            }
            
            public function headings(): array
            {
                return [
                    'property_no',
                    'old_property_no', 
                    'serial_number',
                    'equipment_type',
                    'brand',
                    'model',
                    'specifications',
                    'category',
                    'classification',
                    'is_dcp',
                    'dcp_package',
                    'dcp_year',
                    'condition',
                    'is_functional',
                    'accountability_status',
                    'school',
                    'acquisition_cost',
                    'acquisition_date',
                    'mode_of_acquisition',
                    'source_of_acquisition',
                    'supplier',
                    'warranty_end_date',
                    'equipment_location',
                    'remarks',
                ];
            }
        }, $filename);
    }
}