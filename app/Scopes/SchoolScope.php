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
        // Skip during user resolution. `User::$with = ['employee']` triggers an
        // Employee query while SessionGuard is still resolving `$this->user`;
        // calling `Auth::user()` here would recurse and hit max_execution_time.
        // `hasUser()` returns `false` mid-resolution and does NOT trigger it,
        // so the eager-loaded employee skips the scope (correct: we're loading
        // the auth user's own employee, no school filter needed).
        if (! Auth::hasUser()) {
            return;
        }

        $user = Auth::user();

        if (! $user instanceof \App\Models\User || $user->hasRole('super-admin')) {
            return;
        }

        $schoolId = $user->getAttribute('school_id');

        if (! $schoolId) {
            return;
        }

        $builder->where('school_id', $schoolId);
    }
}