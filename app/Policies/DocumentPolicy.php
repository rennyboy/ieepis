<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['sdo-admin', 'school-admin', 'technician', 'viewer']);
    }

    public function view(User $user, Document $document): bool
    {
        if ($user->hasAnyRole(['technician', 'sdo-admin'])) {
            return true;
        }

        if ($user->hasRole('school-admin')) {
            return (int) $user->school_id === (int) $document->school_id;
        }

        return ! $user->school_id || (int) $user->school_id === (int) $document->school_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['technician', 'sdo-admin', 'school-admin']);
    }

    public function update(User $user, Document $document): bool
    {
        if ($document->school_id && (int) $user->school_id !== (int) $document->school_id) {
            return false;
        }

        return $user->hasAnyRole(['technician', 'sdo-admin', 'school-admin']);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasAnyRole(['technician', 'sdo-admin'])
            && (! $user->school_id || (int) $user->school_id === (int) $document->school_id);
    }
}
