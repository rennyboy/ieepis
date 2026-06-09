<?php

namespace App\Http\Controllers;

use App\Exports\PpePhysicalCountExport;
use App\Models\PpePhysicalCount;
use Maatwebsite\Excel\Facades\Excel;

class PpePhysicalCountExcelController extends Controller
{
    public function export(PpePhysicalCount $ppePhysicalCount)
    {
        activity('ppe-physical-count')
            ->performedOn($ppePhysicalCount)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'exported_excel'])
            ->log('Exported Excel report for ' . $ppePhysicalCount->count_number);

        $filename = 'physical-count-' . $ppePhysicalCount->count_number . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new PpePhysicalCountExport($ppePhysicalCount),
            $filename,
        );
    }
}
