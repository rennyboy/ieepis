<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyPanelRole($user);
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($user->hasRole('technician')) {
            return true;
        }

        if ($user->hasRole('school-admin')) {
            return $this->sameSchool($user, $employee);
        }

        if (! $user->school_id) {
            return true;
        }

        return $this->sameSchool($user, $employee);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['sdo-admin', 'school-admin', 'technician']);
    }

    public function update(User $user, Employee $employee): bool
    {
        if ($user->hasRole('technician')) {
            return true;
        }

        if ($user->hasRole('sdo-admin')) {
            return ! $user->school_id || $this->sameSchool($user, $employee);
        }

        if ($user->hasRole('school-admin')) {
            return $this->sameSchool($user, $employee);
        }

        return false;
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasAnyRole(['sdo-admin', 'school-admin'])
            && (! $user->school_id || $this->sameSchool($user, $employee));
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $this->delete($user, $employee);
    }

    public function forceDelete(User $user, Employee $employee): bool
    {
        return false;
    }

    private function hasAnyPanelRole(User $user): bool
    {
        return $user->hasAnyRole(['sdo-admin', 'school-admin', 'technician', 'viewer']);
    }

    private function sameSchool(User $user, Employee $employee): bool
    {
        $userSchoolId = $user->school_id;

        if ($userSchoolId === null) {
            return false;
        }

        return (int) $userSchoolId === (int) $employee->school_id;
    }
}
