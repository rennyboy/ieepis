<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\EmployeePdfController;
use App\Http\Controllers\EquipmentPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/up', function () {
    return response()->json(['status' => 'ok', 'system' => 'IEEPIS', 'version' => '1.0.0']);
});

Route::middleware('throttle:oauth')->group(function (): void {
    Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
    Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
});

Route::middleware(['auth', 'role:super-admin|sdo-admin|school-admin'])->group(function (): void {
    Route::get('employees/pdf/bulk', [EmployeePdfController::class, 'generateBulkPdf'])
        ->name('employees.pdf.bulk');

    Route::get('equipment/pdf/bulk', [EquipmentPdfController::class, 'generateBulkPdf'])
        ->name('equipment.pdf.bulk');
});
