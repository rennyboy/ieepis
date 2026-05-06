<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;

class EquipmentPolicy
{
    /**
     * Anyone with a panel role may list equipment — SchoolScope further filters
     * the rows each user actually sees.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyPanelRole($user);
    }

    /**
     * Determine if the user can view a specific equipment record.
     */
    public function view(User $user, Equipment $equipment): bool
    {
        if ($user->hasRole('technician')) {
            return true;
        }

        if ($user->hasRole('school-admin')) {
            return $this->sameSchool($user, $equipment);
        }

        // sdo-admin, viewer, and any custom role with no school binding can read.
        if (! $user->school_id) {
            return true;
        }

        return $this->sameSchool($user, $equipment);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['technician', 'sdo-admin', 'school-admin']);
    }

    public function update(User $user, Equipment $equipment): bool
    {
        if ($user->hasRole('technician')) {
            return true;
        }

        if ($user->hasRole('sdo-admin')) {
            return ! $user->school_id || $this->sameSchool($user, $equipment);
        }

        if ($user->hasRole('school-admin')) {
            return $this->sameSchool($user, $equipment);
        }

        return false;
    }

    public function delete(User $user, Equipment $equipment): bool
    {
        return $user->hasAnyRole(['technician', 'sdo-admin'])
            && (! $user->school_id || $this->sameSchool($user, $equipment));
    }

    public function restore(User $user, Equipment $equipment): bool
    {
        return $this->delete($user, $equipment);
    }

    public function forceDelete(User $user, Equipment $equipment): bool
    {
        // Hard delete reserved for super-admin (handled by Gate::before).
        return false;
    }

    private function hasAnyPanelRole(User $user): bool
    {
        return $user->hasAnyRole(['sdo-admin', 'school-admin', 'technician', 'viewer']);
    }

    /**
     * Compare school binding. Treat a null user school_id as "no match" for
     * school-admins — they MUST have a school to scope to.
     */
    private function sameSchool(User $user, Equipment $equipment): bool
    {
        $userSchoolId = $user->school_id;

        if ($userSchoolId === null) {
            return false;
        }

        return (int) $userSchoolId === (int) $equipment->school_id;
    }
}
