<?php

namespace App\Policies;

use App\Enums\PhysicalCountStatus;
use App\Models\PpePhysicalCount;
use App\Models\User;

class PpePhysicalCountPolicy
{
    /**
     * Anyone with a panel role may list physical counts — SchoolScope further
     * filters the rows each user actually sees.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyPanelRole($user);
    }

    public function view(User $user, PpePhysicalCount $count): bool
    {
        if ($user->hasAnyRole(['technician', 'sdo-admin'])) {
            return true;
        }

        if ($user->hasRole('school-admin')) {
            return $this->sameSchool($user, $count);
        }

        // viewer and any custom role without a school binding can read.
        if (! $user->school_id) {
            return true;
        }

        return $this->sameSchool($user, $count);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['technician', 'sdo-admin', 'school-admin']);
    }

    /**
     * Only draft counts may be edited.
     */
    public function update(User $user, PpePhysicalCount $count): bool
    {
        if ($count->status !== PhysicalCountStatus::Draft) {
            return false;
        }

        if ($user->hasAnyRole(['technician', 'sdo-admin'])) {
            return ! $user->school_id || $this->sameSchool($user, $count);
        }

        if ($user->hasRole('school-admin')) {
            return $this->sameSchool($user, $count);
        }

        return false;
    }

    public function delete(User $user, PpePhysicalCount $count): bool
    {
        if ($count->status !== PhysicalCountStatus::Draft) {
            return false;
        }

        return $user->hasAnyRole(['technician', 'sdo-admin'])
            && (! $user->school_id || $this->sameSchool($user, $count));
    }

    public function restore(User $user, PpePhysicalCount $count): bool
    {
        return $this->delete($user, $count);
    }

    public function forceDelete(User $user, PpePhysicalCount $count): bool
    {
        // Reserved for super-admin (handled by Gate::before).
        return false;
    }

    private function hasAnyPanelRole(User $user): bool
    {
        return $user->hasAnyRole(['sdo-admin', 'school-admin', 'technician', 'viewer']);
    }

    private function sameSchool(User $user, PpePhysicalCount $count): bool
    {
        $userSchoolId = $user->school_id;

        if ($userSchoolId === null) {
            return false;
        }

        return (int) $userSchoolId === (int) $count->school_id;
    }
}
