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

        // Find school by name or code, create if not exists
        $school = null;
        if (!empty($row['school'])) {
            $school = School::where('name', 'like', '%' . $row['school'] . '%')
                ->orWhere('school_code', $row['school'])
                ->first();
        }

        // If no school found, use current user's school
        $schoolId = $school?->id ?? Auth::user()?->school_id;

        // Check if property_no already exists
        $existingEquipment = Equipment::where('property_no', $row['property_no'])->first();

        $equipmentData = [
            'school_id' => $schoolId,
            'property_no' => $row['property_no'],
            'old_property_no' => $row['old_property_no'] ?? null,
            'serial_number' => $row['serial_number'] ?? null,
            'equipment_type' => $row['equipment_type'] ?? $row['type'] ?? null,
            'brand' => $row['brand'] ?? null,
            'model' => $row['model'] ?? null,
            'specifications' => $row['specifications'] ?? null,
            'category' => $row['category'] ?? 'High-Value',
            'classification' => $row['classification'] ?? 'Machinery and Equipment for ICT',
            'is_dcp' => in_array(strtolower($row['is_dcp'] ?? ''), ['yes', 'true', '1']),
            'dcp_package' => $row['dcp_package'] ?? null,
            'dcp_year' => $row['dcp_year'] ?? null,
            'condition' => $row['condition'] ?? 'Good',
            'is_functional' => !in_array(strtolower($row['is_functional'] ?? ''), ['no', 'false', '0']),
            'accountability_status' => $row['accountability_status'] ?? 'unassigned',
            'acquisition_cost' => $row['acquisition_cost'] ?? null,
            'acquisition_date' => $this->parseDate($row['acquisition_date'] ?? null),
            'mode_of_acquisition' => $row['mode_of_acquisition'] ?? null,
            'source_of_acquisition' => $row['source_of_acquisition'] ?? null,
            'supplier' => $row['supplier'] ?? null,
            'warranty_end_date' => $this->parseDate($row['warranty_end_date'] ?? null),
            'equipment_location' => $row['equipment_location'] ?? null,
            'remarks' => $row['remarks'] ?? null,
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