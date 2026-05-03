<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OfflineEquipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $equipment = Equipment::query()
            ->select([
                'id',
                'school_id',
                'property_no',
                'old_property_no',
                'serial_number',
                'item_type',
                'equipment_type',
                'brand',
                'model',
                'condition',
                'accountability_status',
                'equipment_location',
                'updated_at',
            ])
            ->orderByDesc('updated_at')
            ->limit(2000)
            ->get();

        return response()->json([
            'fetched_at' => now()->toIso8601String(),
            'count' => $equipment->count(),
            'equipment' => $equipment,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $entries = $request->input('entries', []);

        if (empty($entries) || ! is_array($entries)) {
            return response()->json([
                'message' => 'No entries provided.',
                'synced' => [],
                'failed' => [],
            ]);
        }

        $synced = [];
        $failed = [];
        $defaultSchoolId = Auth::user()?->getAttribute('school_id');

        foreach ($entries as $entry) {
            $clientId = $entry['client_id'] ?? null;

            try {
                $data = $this->validateEntry($entry, $defaultSchoolId);

                $equipment = DB::transaction(fn () => Equipment::create($data));

                $synced[] = [
                    'client_id' => $clientId,
                    'id' => $equipment->id,
                    'property_no' => $equipment->property_no,
                ];
            } catch (ValidationException $e) {
                $failed[] = [
                    'client_id' => $clientId,
                    'property_no' => $entry['property_no'] ?? null,
                    'errors' => $e->errors(),
                ];
            } catch (\Throwable $e) {
                $failed[] = [
                    'client_id' => $clientId,
                    'property_no' => $entry['property_no'] ?? null,
                    'errors' => ['exception' => [$e->getMessage()]],
                ];
            }
        }

        return response()->json([
            'synced_count' => count($synced),
            'failed_count' => count($failed),
            'synced' => $synced,
            'failed' => $failed,
        ]);
    }

    private function validateEntry(array $entry, ?int $defaultSchoolId): array
    {
        if (! isset($entry['school_id']) && $defaultSchoolId) {
            $entry['school_id'] = $defaultSchoolId;
        }

        return validator($entry, [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'property_no' => ['required', 'string', 'max:255', Rule::unique('equipment', 'property_no')],
            'old_property_no' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'item_type' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'specifications' => ['nullable', 'string'],
            'category' => ['nullable', Rule::in(['High-Value', 'Low-Value'])],
            'condition' => ['nullable', Rule::in(['Good', 'Fair', 'Poor', 'Unserviceable'])],
            'equipment_location' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ])->validate();
    }
}
