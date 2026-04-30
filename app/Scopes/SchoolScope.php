<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * SchoolScope - Global Query Scope for school-based filtering
 *
 * This scope automatically filters models by school_id for non-super-admin users.
 * It ensures that users can only see data from their assigned school.
 *
 * Apply to models with school_id column:
 * - EquipmentAssignment
 * - Equipment
 * - Employee
 * - Document
 * - Ticket
 *
 * Usage in model:
 * protected static function booted(): void
 * {
 *     static::addGlobalScope(new SchoolScope());
 * }
 */
class SchoolScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Skip filtering if:
        // 1. No user is authenticated
        // 2. User is a super-admin
        if (!$user || $user->hasRole('super-admin')) {
            return;
        }

        // Get school_id from user column (direct access, safe)
        $schoolId = $user->school_id;

        // Skip if no school_id
        if (!$schoolId) {
            return;
        }

        // Filter by user's school_id
        $builder->where('school_id', $schoolId);
    }
}