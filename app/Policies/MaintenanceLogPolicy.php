<?php

namespace App\Policies;

use App\Models\MaintenanceLog;
use App\Models\User;

class MaintenanceLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['sdo-admin', 'school-admin', 'technician', 'viewer']);
    }

    public function view(User $user, MaintenanceLog $log): bool
    {
        if ($user->hasAnyRole(['technician', 'sdo-admin'])) {
            return true;
        }

        if ($user->hasRole('school-admin')) {
            return $log->equipment && (int) $user->school_id === (int) $log->equipment->school_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['technician', 'sdo-admin', 'school-admin']);
    }

    public function update(User $user, MaintenanceLog $log): bool
    {
        return $user->hasAnyRole(['technician', 'sdo-admin']);
    }

    public function delete(User $user, MaintenanceLog $log): bool
    {
        return $user->hasAnyRole(['technician', 'sdo-admin']);
    }
}
