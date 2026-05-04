<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Equipment;
use App\Models\Employee;
use App\Http\Requests\ScanResolveRequest;
use App\Http\Requests\ScanSyncRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

class OfflineSyncController extends Controller
{
    /**
     * Handle an online scan (direct resolution)
     */
    public function resolve(ScanResolveRequest $request)
    {
        $code = trim($request->validated('code'));

        try {
            // 1. Check for system-generated QR format (IEEPIS|property_no|serial_number|brand model)
            if (str_starts_with($code, 'IEEPIS|')) {
                $parts = explode('|', $code);
                if (count($parts) >= 2) {
                    $propertyNo = $parts[1];
                    $equipment = Equipment::withTrashed()->where('property_no', $propertyNo)->firstOrFail();
                    Gate::authorize('view', $equipment);
                    
                    return response()->json([
                        'redirect' => \App\Filament\Resources\EquipmentResource::getUrl('edit', ['record' => $equipment])
                    ]);
                }
            }

            // 2. Legacy format check (EQ-123, EM-123)
            $parts = explode('-', $code);
            if (count($parts) === 2 && in_array(strtoupper($parts[0]), ['EQ', 'EM'])) {
                [$prefix, $id] = $parts;
                $prefix = strtoupper($prefix);

                if ($prefix === 'EQ') {
                    $equipment = Equipment::withTrashed()->findOrFail($id);
                    Gate::authorize('view', $equipment);
                    
                    return response()->json([
                        'redirect' => \App\Filament\Resources\EquipmentResource::getUrl('edit', ['record' => $equipment])
                    ]);
                } elseif ($prefix === 'EM') {
                    $employee = Employee::withTrashed()->findOrFail($id);
                    Gate::authorize('view', $employee);
                    
                    return response()->json([
                        'redirect' => \App\Filament\Resources\EmployeeResource::getUrl('edit', ['record' => $employee])
                    ]);
                }
            }

            // 3. Fallback for raw property number search (manual entry)
            $equipment = Equipment::withTrashed()->where('property_no', $code)->first();
            if ($equipment) {
                Gate::authorize('view', $equipment);
                
                return response()->json([
                    'redirect' => \App\Filament\Resources\EquipmentResource::getUrl('edit', ['record' => $equipment])
                ]);
            }

            return response()->json(['message' => 'Unrecognized QR Code format or record not found.'], 400);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found in the database.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'You do not have permission to view this record.'], 403);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while resolving the QR code.'], 500);
        }
    }

    /**
     * Handle syncing a batch of offline scans
     */
    public function sync(ScanSyncRequest $request)
    {
        $scans = $request->validated('scans');
        
        if (empty($scans)) {
            return response()->json(['message' => 'No scans provided.', 'synced_count' => 0]);
        }

        $propertyNumbers = [];
        $equipmentIds = [];
        $employeeIds = [];

        // Pre-parse the scans to gather keys for bulk lookup
        foreach ($scans as $scan) {
            $code = trim($scan['code'] ?? '');
            if (!$code) continue;

            if (str_starts_with($code, 'IEEPIS|')) {
                $parts = explode('|', $code);
                if (count($parts) >= 2) {
                    $propertyNumbers[] = $parts[1];
                }
            } else {
                $parts = explode('-', $code);
                if (count($parts) === 2 && in_array(strtoupper($parts[0]), ['EQ', 'EM'])) {
                    [$prefix, $id] = $parts;
                    if (strtoupper($prefix) === 'EQ') $equipmentIds[] = $id;
                    if (strtoupper($prefix) === 'EM') $employeeIds[] = $id;
                } else {
                    $propertyNumbers[] = $code;
                }
            }
        }

        // Perform bulk queries to verify records exist and avoid N+1 inside the loop
        $validEquipmentIds = empty($equipmentIds) ? collect() : Equipment::withTrashed()->whereIn('id', $equipmentIds)->pluck('id');
        $validEquipmentProps = empty($propertyNumbers) ? collect() : Equipment::withTrashed()->whereIn('property_no', $propertyNumbers)->pluck('property_no');
        $validEmployeeIds = empty($employeeIds) ? collect() : Employee::withTrashed()->whereIn('id', $employeeIds)->pluck('id');

        $syncedCount = 0;

        foreach ($scans as $scan) {
            $code = trim($scan['code'] ?? '');
            if (!$code) continue;

            $found = false;

            if (str_starts_with($code, 'IEEPIS|')) {
                $parts = explode('|', $code);
                if (count($parts) >= 2 && $validEquipmentProps->contains($parts[1])) {
                    $found = true;
                }
            } else {
                $parts = explode('-', $code);
                if (count($parts) === 2 && in_array(strtoupper($parts[0]), ['EQ', 'EM'])) {
                    [$prefix, $id] = $parts;
                    if (strtoupper($prefix) === 'EQ' && $validEquipmentIds->contains($id)) $found = true;
                    if (strtoupper($prefix) === 'EM' && $validEmployeeIds->contains($id)) $found = true;
                } else {
                    if ($validEquipmentProps->contains($code)) $found = true;
                }
            }

            if ($found) {
                // In a full implementation, you might create an "Audit Log" or update a "last seen" timestamp here.
                $syncedCount++;
            }
        }

        return response()->json([
            'message' => 'Sync completed',
            'synced_count' => $syncedCount
        ]);
    }
}
