<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Barryvdh\DomPDF\Facade\Pdf;

class EquipmentPdfController extends Controller
{
    public function generateBulkPdf()
    {
        $equipment = Equipment::query()
            ->with(['school', 'activeAssignment.employee'])
            ->orderBy('property_no')
            ->get();

        $pdf = Pdf::loadView('pdf.equipment-list', [
            'equipment' => $equipment,
        ]);

        return $pdf->download('equipment-inventory-'.now()->format('Y-m-d').'.pdf');
    }
}
