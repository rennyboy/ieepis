<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;

class EmployeePdfController extends Controller
{
    public function generateBulkPdf()
    {
        $employees = Employee::query()
            ->with('school')
            ->withCount('activeAssignments')
            ->orderBy('last_name')
            ->get();

        $pdf = Pdf::loadView('pdf.employees-list', [
            'employees' => $employees,
        ]);

        return $pdf->download('employees-list-'.now()->format('Y-m-d').'.pdf');
    }
}
