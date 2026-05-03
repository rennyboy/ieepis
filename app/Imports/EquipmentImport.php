<?php

namespace App\Imports;

use App\Models\Equipment;
use App\Models\School;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EquipmentImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    private int $rowsImported = 0;

    public function getRowCount(): int
    {
        return $this->rowsImported;
    }
    public function model(array $row)
    {
        $this->rowsImported++;

        // Advanced keyword-based matching for complex headers
        $get = function (array $row, array $keywords, array $exclude = [], $default = null) {
            foreach ($row as $rowKey => $rowValue) {
                $normalizedKey = strtolower($rowKey);
                
                // Exclude if it contains unwanted keywords (e.g. 'old' when looking for current property)
                $shouldExclude = false;
                foreach ($exclude as $ex) {
                    if (str_contains($normalizedKey, strtolower($ex))) {
                        $shouldExclude = true;
                        break;
                    }
                }
                if ($shouldExclude) continue;

                // Try keyword combination match
                $allMatch = true;
                foreach ($keywords as $kw) {
                    if (!str_contains($normalizedKey, strtolower($kw))) {
                        $allMatch = false;
                        break;
                    }
                }
                if ($allMatch) return $rowValue;
            }
            return $default;
        };

        // Find school by name or code
        $schoolVal = $get($row, ['school']) ?? $get($row, ['station']) ?? $get($row, ['office']);
        $school = null;
        if (!empty($schoolVal)) {
            $school = School::where('name', 'like', '%' . $schoolVal . '%')
                ->orWhere('school_code', $schoolVal)
                ->first();
        }

        $schoolId = $school?->id ?? Auth::user()?->school_id;

        // Strict mapping for property_no to avoid 'old_property_no'
        $propertyNo = $get($row, ['property', 'no'], ['old', 'previous']) ?? $get($row, ['asset', 'id']);
        $existingEquipment = Equipment::where('property_no', $propertyNo)->first();

        $equipmentData = [
            'school_id' => $schoolId,
            'property_no' => $propertyNo,
            'old_property_no' => $get($row, ['old', 'property']) ?? $get($row, ['previous', 'property']),
            'serial_number' => $get($row, ['serial', 'no']) ?? $get($row, ['sn']) ?? $get($row, ['s/n']),
            'equipment_type' => $get($row, ['item', 'dropdown']) ?? $get($row, ['equipment', 'type']) ?? $get($row, ['item', 'type']),
            'brand' => $get($row, ['brand']) ?? $get($row, ['manufacturer']),
            'model' => $get($row, ['model']),
            'specifications' => $get($row, ['specifications']) ?? $get($row, ['specs']),
            'category' => $get($row, ['category']) ?? 'High-Value',
            'classification' => $get($row, ['classification']) ?? 'Machinery and Equipment for ICT',
            'is_dcp' => !in_array(strtolower($get($row, ['non', 'dcp']) ?? 'yes'), ['yes', 'true', '1', '✓', 'x']),
            'dcp_package' => $get($row, ['dcp', 'package']) ?? $get($row, ['batch', 'name']),
            'dcp_year' => $get($row, ['dcp', 'year']) ?? $get($row, ['batch', 'year']) ?? $get($row, ['batch']),
            'condition' => $get($row, ['condition']) ?? 'Good',
            'is_functional' => !in_array(strtolower($get($row, ['non', 'functional']) ?? ''), ['yes', 'true', '1', '✓', 'x']),
            'accountability_status' => $get($row, ['accountability']) ?? $get($row, ['assignment']) ?? 'unassigned',
            'acquisition_cost' => $get($row, ['cost']) ?? $get($row, ['amount']),
            'acquisition_date' => $this->parseDate($get($row, ['date', 'acquired']) ?? $get($row, ['date', 'received'])),
            'mode_of_acquisition' => $get($row, ['mode', 'acquisition']),
            'source_of_acquisition' => $get($row, ['source', 'acquisition']),
            'supplier' => $get($row, ['supplier']) ?? $get($row, ['vendor']),
            'warranty_end_date' => $this->parseDate($get($row, ['warranty', 'end'])),
            'equipment_location' => $get($row, ['location']) ?? $get($row, ['room']),
            'remarks' => $get($row, ['remarks']),
        ];

        if ($existingEquipment) {
            // Update existing equipment
            $existingEquipment->update($equipmentData);
            return null; // Don't create new
        }

        return new Equipment($equipmentData);
    }

    public function rules(): array
    {
        return [
            'property_no' => ['required', 'string', 'max:255'],
            'equipment_type' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function prepareForValidation($data, $index)
    {
        if (isset($data['property_no'])) {
            $data['property_no'] = (string) $data['property_no'];
        }
        if (isset($data['equipment_type'])) {
            $data['equipment_type'] = (string) $data['equipment_type'];
        }
        if (isset($data['brand'])) {
            $data['brand'] = (string) $data['brand'];
        }
        if (isset($data['model'])) {
            $data['model'] = (string) $data['model'];
        }
        
        return $data;
    }

    public function onFailure(\Maatwebsite\Excel\Validators\Failure $failures)
    {
        // Handle validation failures if needed
    }

    private function parseDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}