<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\School;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class EmployeeImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
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

        if (! empty($row['__skip_row'])
            || (empty($row['first_name']) && empty($row['last_name']) && empty($row['employee_number']))) {
            return null;
        }

        if (empty($row['first_name']) || empty($row['last_name'])) {
            return null;
        }

        if (empty($row['employee_number'])) {
            $row['employee_number'] = 'AUTO-'.Str::upper(Str::slug($row['first_name'].'-'.$row['last_name']));
        }

        $this->rowsImported++;

        $school = null;
        if (! empty($row['school'])) {
            $school = School::where('name', 'like', '%'.$row['school'].'%')
                ->orWhere('school_code', $row['school'])
                ->first();
        }
        $schoolId = $this->schoolId ?? $school?->id ?? Auth::user()?->school_id;

        $employeeData = [
            'school_id' => $schoolId,
            'employee_number' => $row['employee_number'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'middle_name' => $row['middle_name'] ?? null,
            'suffix' => $row['suffix'] ?? null,
            'position' => $row['position'] ?? 'Unspecified',
            'department' => $row['department'] ?? null,
            'ro_office' => $row['ro_office'] ?? null,
            'sdo_office' => $row['sdo_office'] ?? null,
            'employment_type' => $row['employment_type'] ?? 'teaching',
            'status' => $row['status'] ?? 'active',
            'email' => $row['email'] ?? null,
            'personal_email' => $row['personal_email'] ?? null,
            'mobile_1' => $row['mobile_1'] ?? null,
            'mobile_2' => $row['mobile_2'] ?? null,
            'date_hired' => $this->parseDate($row['date_hired'] ?? null),
            'is_oic' => $this->parseFlag($row['is_oic'] ?? null),
            'oic_office' => $row['oic_office'] ?? null,
            'is_non_deped_funded' => $this->parseFlag($row['is_non_deped_funded'] ?? null),
            'is_inactive' => $this->parseFlag($row['is_inactive'] ?? null),
            'date_of_separation' => $this->parseDate($row['date_of_separation'] ?? null),
            'cause_of_separation' => $row['cause_of_separation'] ?? null,
            'detailed_from' => $row['detailed_from'] ?? null,
            'detailed_to' => $row['detailed_to'] ?? null,
        ];

        $existing = Employee::where('employee_number', $row['employee_number'])->first();
        if ($existing) {
            $existing->update($employeeData);

            return null;
        }

        return new Employee($employeeData);
    }

    public function rules(): array
    {
        return [
            'employee_number' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function prepareForValidation($data, $index)
    {
        $row = $this->normalizeRow($data);

        $row['__skip_row'] = empty($row['employee_number'])
            && empty($row['first_name'])
            && empty($row['last_name']);

        return $row;
    }

    public function onFailure(\Maatwebsite\Excel\Validators\Failure $failures) {}

    private function normalizeRow(array $row): array
    {
        $aliases = [
            'employee_number' => [['employee', 'number'], ['mobile', 'contact']],
            'first_name' => [['first', 'name'], ['surname']],
            'last_name' => [['last', 'name'], ['first']],
            'middle_name' => [['middle', 'name'], []],
            'suffix' => [['suffix'], []],
            'position' => [['position'], ['oic']],
            'department' => [['department'], []],
            'ro_office' => [['ro', 'office'], ['sdo']],
            'sdo_office' => [['sdo', 'office'], []],
            'employment_type' => [['employment', 'type'], []],
            'status' => [['status'], ['oic', 'inactive', 'separation', 'employment']],
            'school' => [['school'], ['preschool', 'high_school_grad']],
            'email' => [['email'], ['personal']],
            'personal_email' => [['personal', 'email'], []],
            'mobile_1' => [['mobile_1'], []],
            'mobile_2' => [['mobile_2'], []],
            'date_hired' => [['date', 'hired'], []],
            'is_oic' => [['oic'], ['office']],
            'oic_office' => [['oic', 'office'], []],
            'is_non_deped_funded' => [['non', 'deped'], []],
            'is_inactive' => [['inactive'], []],
            'date_of_separation' => [['separation'], ['cause']],
            'cause_of_separation' => [['cause', 'separation'], []],
            'detailed_from' => [['detailed', 'from'], []],
            'detailed_to' => [['detailed', 'to'], []],
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

        $stringFields = [
            'employee_number', 'first_name', 'last_name', 'middle_name', 'suffix',
            'position', 'department', 'ro_office', 'sdo_office', 'employment_type',
            'status', 'school', 'email', 'personal_email', 'mobile_1', 'mobile_2',
            'oic_office', 'cause_of_separation', 'detailed_from', 'detailed_to',
        ];
        foreach ($stringFields as $f) {
            if (isset($row[$f]) && $row[$f] !== null && $row[$f] !== '') {
                $row[$f] = (string) $row[$f];
            }
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

    private function parseFlag($value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return in_array(strtolower((string) $value), ['yes', 'true', '1', '✓', 'x', 'oic'], true);
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
}
