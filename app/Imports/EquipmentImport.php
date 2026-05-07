<?php

namespace App\Imports;

use App\Models\Equipment;
use App\Models\School;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Auth;

class EquipmentImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    private int $rowsImported = 0;

    public function __construct(private ?int $schoolId = null) {}

    public function getRowCount(): int
    {
        return $this->rowsImported;
    }

    public function model(array $row)
    {
        $row = $this->normalizeRow($row);

        if (! empty($row['__skip_row']) || empty($row['property_no'])) {
            return null;
        }

        $this->rowsImported++;

        $school = null;
        if (! empty($row['school'])) {
            $school = School::where('name', 'like', '%'.$row['school'].'%')
                ->orWhere('school_code', $row['school'])
                ->first();
        }
        $schoolId = $this->schoolId ?? $school?->id ?? Auth::user()?->school_id;

        $equipmentData = [
            'school_id' => $schoolId,
            'property_no' => $row['property_no'],
            'old_property_no' => $row['old_property_no'] ?? null,
            'serial_number' => $row['serial_number'] ?? null,
            'equipment_type' => $row['equipment_type'] ?? null,
            'brand' => $row['brand'] ?? null,
            'model' => $row['model'] ?? null,
            'specifications' => $row['specifications'] ?? null,
            'category' => $this->mapCategory($row['category'] ?? null),
            'classification' => $row['classification'] ?? 'Machinery and Equipment for ICT',
            'is_dcp' => $this->parseInverseFlag($row['non_dcp_flag'] ?? null),
            'dcp_package' => $row['dcp_package'] ?? null,
            'dcp_year' => $row['dcp_year'] ?? null,
            'condition' => $this->mapCondition($row['condition'] ?? null),
            'is_functional' => $this->parseInverseFlag($row['non_functional_flag'] ?? null),
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

        $existing = Equipment::where('property_no', $row['property_no'])->first();
        if ($existing) {
            $existing->update($equipmentData);

            return null;
        }

        return new Equipment($equipmentData);
    }

    public function rules(): array
    {
        return [
            'property_no' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function prepareForValidation($data, $index)
    {
        $row = $this->normalizeRow($data);

        $row['__skip_row'] = empty($row['property_no'])
            && empty($row['serial_number'])
            && empty($row['equipment_type']);

        return $row;
    }

    public function onFailure(\Maatwebsite\Excel\Validators\Failure $failures) {}

    private function normalizeRow(array $row): array
    {
        $aliases = [
            'property_no' => [['property', 'no'], ['old', 'previous']],
            'old_property_no' => [['old', 'property'], []],
            'serial_number' => [['serial'], []],
            'equipment_type' => [['item'], []],
            'brand' => [['brand'], []],
            'model' => [['model'], ['mode_of', 'modeofac']],
            'specifications' => [['specifications'], []],
            'category' => [['category'], []],
            'classification' => [['classification'], ['coa']],
            'school' => [['school'], ['preschool']],
            'dcp_package' => [['dcp', 'package'], []],
            'dcp_year' => [['dcp', 'year'], []],
            'condition' => [['condition'], []],
            'accountability_status' => [['accountability', 'disposition'], []],
            'acquisition_cost' => [['acquisition', 'cost'], []],
            'acquisition_date' => [['acquisition', 'date'], ['cost', 'mode', 'source']],
            'mode_of_acquisition' => [['mode', 'acquisition'], []],
            'source_of_acquisition' => [['source', 'acquisition'], []],
            'supplier' => [['supplier'], []],
            'warranty_end_date' => [['warranty'], ['under']],
            'equipment_location' => [['location'], []],
            'remarks' => [['remarks'], []],
            'non_dcp_flag' => [['non', 'dcp'], []],
            'non_functional_flag' => [['non', 'functional'], []],
        ];

        foreach ($aliases as $canonical => [$keywords, $exclude]) {
            if (! empty($row[$canonical])) {
                continue;
            }
            $value = $this->fuzzyGet($row, $keywords, $exclude);
            if ($value !== null && $value !== '') {
                $row[$canonical] = $value;
            }
        }

        if (empty($row['property_no']) && ! empty($row['serial_number'])) {
            $row['property_no'] = $row['serial_number'];
        }

        if (! empty($row['equipment_type'])) {
            $row['equipment_type'] = (string) $row['equipment_type'];
        }
        if (! empty($row['property_no'])) {
            $row['property_no'] = (string) $row['property_no'];
        }

        return $row;
    }

    private function fuzzyGet(array $row, array $keywords, array $exclude = []): mixed
    {
        foreach ($row as $rowKey => $rowValue) {
            $key = strtolower((string) $rowKey);

            foreach ($exclude as $ex) {
                if (str_contains($key, strtolower($ex))) {
                    continue 2;
                }
            }

            foreach ($keywords as $kw) {
                if (! str_contains($key, strtolower($kw))) {
                    continue 2;
                }
            }

            return $rowValue;
        }

        return null;
    }

    private function parseInverseFlag($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return ! in_array(strtolower((string) $value), ['yes', 'true', '1', '✓', 'x'], true);
    }

    private function parseDate($date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        if (is_numeric($date) && $date > 1 && $date < 100000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $date)
                    ->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapCategory(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'High-Value';
        }

        return match (strtolower(trim($value))) {
            'low-value', 'low value', 'lv' => 'Low-Value',
            default => 'High-Value',
        };
    }

    private function mapCondition(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'Good';
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'serviceable', 'working', 'operational', 'good' => 'Good',
            'fair', 'fairly serviceable' => 'Fair',
            'poor', 'damaged', 'needs repair' => 'Poor',
            'unserviceable', 'broken', 'non-functional' => 'Unserviceable',
            default => 'Good',
        };
    }
}
