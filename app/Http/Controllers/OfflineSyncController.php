<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;
use App\Models\Employee;

class OfflineSyncController extends Controller
{
    /**
     * Handle an online scan (direct resolution)
     */
    public function resolve(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return response()->json(['message' => 'Invalid QR Code scanned.'], 400);
        }

        $parts = explode('-', $code);
        if (count($parts) !== 2) {
            return response()->json(['message' => 'Unrecognized QR Code format.'], 400);
        }

        [$prefix, $id] = $parts;

        try {
            if ($prefix === 'EQ') {
                $equipment = Equipment::withTrashed()->findOrFail($id);
                return response()->json([
                    'redirect' => \App\Filament\Resources\EquipmentResource::getUrl('edit', ['record' => $equipment])
                ]);
            } elseif ($prefix === 'EM') {
                $employee = Employee::withTrashed()->findOrFail($id);
                return response()->json([
                    'redirect' => \App\Filament\Resources\EmployeeResource::getUrl('edit', ['record' => $employee])
                ]);
            } else {
                return response()->json(['message' => 'Unknown QR prefix.'], 400);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found in the database.'], 404);
        }
    }

    /**
     * Handle syncing a batch of offline scans
     */
    public function sync(Request $request)
    {
        $scans = $request->input('scans', []);
        
        if (empty($scans)) {
            return response()->json(['message' => 'No scans provided.', 'synced_count' => 0]);
        }

        $syncedCount = 0;

        foreach ($scans as $scan) {
            $code = $scan['code'] ?? null;
            if (!$code) continue;

            $parts = explode('-', $code);
            if (count($parts) !== 2) continue;

            [$prefix, $id] = $parts;

            // In a full implementation, you might create an "Audit Log" or update a "last seen" timestamp here.
            // For now, we just verify the record exists.
            try {
                if ($prefix === 'EQ') {
                    $equipment = Equipment::withTrashed()->findOrFail($id);
                    // $equipment->update(['last_seen_at' => now()]);
                    $syncedCount++;
                } elseif ($prefix === 'EM') {
                    $employee = Employee::withTrashed()->findOrFail($id);
                    // $employee->update(['last_seen_at' => now()]);
                    $syncedCount++;
                }
            } catch (\Exception $e) {
                // Ignore missing records during bulk sync, or log them.
                continue;
            }
        }

        return response()->json([
            'message' => 'Sync completed',
            'synced_count' => $syncedCount
        ]);
    }
}
