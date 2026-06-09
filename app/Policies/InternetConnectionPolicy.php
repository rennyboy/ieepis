<?php

namespace App\Policies;

use App\Models\InternetConnection;
use App\Models\User;

class InternetConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['sdo-admin', 'school-admin', 'technician', 'viewer']);
    }

    public function view(User $user, InternetConnection $connection): bool
    {
        if ($user->hasAnyRole(['technician', 'sdo-admin'])) {
            return true;
        }

        if ($user->hasRole('school-admin')) {
            return (int) $user->school_id === (int) $connection->school_id;
        }

        return ! $user->school_id || (int) $user->school_id === (int) $connection->school_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['technician', 'sdo-admin', 'school-admin']);
    }

    public function update(User $user, InternetConnection $connection): bool
    {
        if ($user->hasRole('school-admin')) {
            return (int) $user->school_id === (int) $connection->school_id;
        }

        return $user->hasAnyRole(['technician', 'sdo-admin']);
    }

    public function delete(User $user, InternetConnection $connection): bool
    {
        if ($connection->school_id && (int) $user->school_id !== (int) $connection->school_id) {
            return false;
        }

        return $user->hasAnyRole(['technician', 'sdo-admin']);
    }
}
