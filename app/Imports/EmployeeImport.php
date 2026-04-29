<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\School;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmployeeImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $school = null;
        if (!empty($row['school'])) {
            $school = School::where('name', 'like', '%' . $row['school'] . '%')
                ->orWhere('school_code', $row['school'])
                ->first();
        }

        $schoolId = $school?->id ?? Auth::user()?->school_id;

        $existingEmployee = Employee::where('employee_number', $row['employee_number'])->first();

        $employeeData = [
            'school_id' => $schoolId,
            'employee_number' => $row['employee_number'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'middle_name' => $row['middle_name'] ?? null,
            'suffix' => $row['suffix'] ?? null,
            'position' => $row['position'] ?? null,
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
            'is_oic' => in_array(strtolower($row['is_oic'] ?? ''), ['yes', 'true', '1']),
            'oic_office' => $row['oic_office'] ?? null,
            'is_non_deped_funded' => in_array(strtolower($row['is_non_deped_funded'] ?? ''), ['yes', 'true', '1']),
            'is_inactive' => in_array(strtolower($row['is_inactive'] ?? ''), ['yes', 'true', '1']),
            'date_of_separation' => $this->parseDate($row['date_of_separation'] ?? null),
            'cause_of_separation' => $row['cause_of_separation'] ?? null,
            'detailed_from' => $row['detailed_from'] ?? null,
            'detailed_to' => $row['detailed_to'] ?? null,
        ];

        if ($existingEmployee) {
            $existingEmployee->update($employeeData);
            return null;
        }

        return new Employee($employeeData);
    }

    public function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function onFailure(\Maatwebsite\Excel\Validators\Failure $failures)
    {
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