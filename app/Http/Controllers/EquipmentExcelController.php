<?php

namespace App\Http\Controllers;

use App\Exports\EquipmentExport;
use App\Imports\EquipmentImport;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class EquipmentExcelController extends Controller
{
    public const UNRESOLVED_DIR = 'imports/unresolved';

    public function export()
    {
        $filename = 'equipment_export_'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new EquipmentExport, $filename);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new EquipmentImport;
            Excel::import($import, $request->file('file'));

            $summary = $import->getResolutionSummary();
            $reportPath = self::storeUnresolvedReport($summary);

            self::notifyImportResult($summary, $reportPath);

            return back()->with('success', 'Equipment imported.');
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Import Failed')
                ->body('Error: '.$e->getMessage())
                ->send();

            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    public function downloadUnresolved(string $filename)
    {
        if (! preg_match('/^[A-Z0-9]{26}\.csv$/', $filename)) {
            abort(404);
        }

        $path = self::UNRESOLVED_DIR.'/'.$filename;

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path, 'unresolved-employees.csv');
    }

    public function template()
    {
        $filename = 'equipment_import_template.xlsx';

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
                    'accountable_officer',
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

    /**
     * @param  array{rows:int, officer_resolved:int, custodian_resolved:int, officer_unresolved:list<string>, custodian_unresolved:list<string>}  $summary
     */
    public static function storeUnresolvedReport(array $summary): ?string
    {
        if (empty($summary['officer_unresolved']) && empty($summary['custodian_unresolved'])) {
            return null;
        }

        $csv = "type,name\n";
        foreach ($summary['officer_unresolved'] as $name) {
            $csv .= 'officer,"'.str_replace('"', '""', $name)."\"\n";
        }
        foreach ($summary['custodian_unresolved'] as $name) {
            $csv .= 'custodian,"'.str_replace('"', '""', $name)."\"\n";
        }

        $disk = Storage::disk('local');
        $filename = Str::ulid().'.csv';

        try {
            if (! $disk->exists(self::UNRESOLVED_DIR)) {
                $disk->makeDirectory(self::UNRESOLVED_DIR);
            }
            $disk->put(self::UNRESOLVED_DIR.'/'.$filename, $csv);
        } catch (\Throwable $e) {
            // The import succeeded; a report-write failure (usually a
            // filesystem permission hiccup) must not mask that. Best-effort
            // log, then fall back to a notification without the download link.
            try {
                report($e);
            } catch (\Throwable) {
            }

            return null;
        }

        return $filename;
    }

    /**
     * @param  array{rows:int, officer_resolved:int, custodian_resolved:int, officer_unresolved:list<string>, custodian_unresolved:list<string>}  $summary
     */
    public static function notifyImportResult(array $summary, ?string $reportFilename): void
    {
        $unresolvedCount = count($summary['officer_unresolved']) + count($summary['custodian_unresolved']);

        $body = sprintf(
            'Imported %d row(s). Officer set on %d, custodian set on %d.',
            $summary['rows'],
            $summary['officer_resolved'],
            $summary['custodian_resolved'],
        );

        if ($unresolvedCount > 0) {
            $body .= sprintf(
                ' %d officer name(s) and %d custodian name(s) could not be matched — download the CSV to seed the missing employees.',
                count($summary['officer_unresolved']),
                count($summary['custodian_unresolved']),
            );
        }

        $notification = Notification::make()
            ->title($unresolvedCount > 0 ? 'Import Completed with Warnings' : 'Import Successful')
            ->body($body)
            ->{$unresolvedCount > 0 ? 'warning' : 'success'}();

        if ($reportFilename !== null) {
            $notification = $notification->actions([
                Action::make('download_unresolved')
                    ->label('Download Unresolved CSV')
                    ->url(route('equipment.excel.unresolved', ['filename' => $reportFilename]))
                    ->openUrlInNewTab(),
            ])->persistent();
        }

        $notification->send();
    }
}
