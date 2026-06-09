<?php

namespace App\Http\Controllers;

use App\Models\PpePhysicalCount;
use Barryvdh\DomPDF\Facade\Pdf;

class PpePhysicalCountPdfController extends Controller
{
    public function generate(PpePhysicalCount $ppePhysicalCount)
    {
        $ppePhysicalCount->load([
            'school',
            'conductedByEmployee',
            'verifiedByEmployee',
            'items',
        ]);

        $pdf = Pdf::loadView('pdf.ppe-physical-count', [
            'count' => $ppePhysicalCount,
            'items' => $ppePhysicalCount->items,
            'totalShortageValue' => $ppePhysicalCount->items->sum('shortage_value'),
            'totalOverageValue' => $ppePhysicalCount->items->sum('overage_value'),
        ]);

        $pdf->setPaper('legal', 'landscape');

        activity('ppe-physical-count')
            ->performedOn($ppePhysicalCount)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'exported_pdf'])
            ->log('Exported PDF report for ' . $ppePhysicalCount->count_number);

        return $pdf->download(
            'physical-count-' . $ppePhysicalCount->count_number . '-' . now()->format('Y-m-d') . '.pdf',
        );
    }
}
