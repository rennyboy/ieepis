<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

/**
 * Mirrors EquipmentPolicy — maintenance tickets are equipment/IT-adjacent and
 * share the same school-scoping model. Super-admin is short-circuited globally
 * by Gate::before (AppServiceProvider), so it is intentionally not handled here.
 * Row-level visibility is further narrowed by TicketResource::getEloquentQuery();
 * this policy gates the actions (create/update/delete) the query scope cannot.
 */
class TicketPolicy
{
    /**
     * Anyone with a panel role may list tickets — the resource query scope
     * further filters the rows each user actually sees.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyPanelRole($user);
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole('technician')) {
            return true;
        }

        if ($user->hasRole('school-admin')) {
            return $this->sameSchool($user, $ticket);
        }

        // sdo-admin, viewer, and any custom role with no school binding can read.
        if (! $user->school_id) {
            return true;
        }

        return $this->sameSchool($user, $ticket);
    }

    /**
     * School-admins file tickets for their own school; technicians and
     * sdo-admins triage across schools.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['technician', 'sdo-admin', 'school-admin']);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole('technician')) {
            return true;
        }

        if ($user->hasRole('sdo-admin')) {
            return ! $user->school_id || $this->sameSchool($user, $ticket);
        }

        if ($user->hasRole('school-admin')) {
            return $this->sameSchool($user, $ticket);
        }

        return false;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['technician', 'sdo-admin'])
            && (! $user->school_id || $this->sameSchool($user, $ticket));
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        return $this->delete($user, $ticket);
    }

    public function forceDelete(User $user, Ticket $ticket): bool
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
    private function sameSchool(User $user, Ticket $ticket): bool
    {
        $userSchoolId = $user->school_id;

        if ($userSchoolId === null) {
            return false;
        }

        return (int) $userSchoolId === (int) $ticket->school_id;
    }
}
