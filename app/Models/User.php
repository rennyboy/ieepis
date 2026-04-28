<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model — auth identity only.
 *
 * Personal/organizational data (full_name, school_id, division_id) lives on
 * `Employee`. A User links to an Employee via `employees.user_id`. Reads of
 * `$user->name`, `$user->school_id`, `$user->division_id` are delegated to the
 * employee — no callsite changes required.
 *
 * @property int $id
 * @property string $email
 * @property string|null $password
 * @property string|null $google_id
 * @property string|null $approval_status
 * @property string|null $remember_token
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * Delegated (read-only via accessor):
 * @property-read string $name
 * @property-read int|null $school_id
 * @property-read int|null $division_id
 * @property-read \App\Models\School|null $school
 *
 * @method bool hasRole(string|array $roles)
 * @method bool hasAnyRole(string|array $roles)
 * @method \Illuminate\Support\Collection getRoleNames()
 *
 * @mixin \Spatie\Permission\Traits\HasRoles
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['email', 'password', 'approval_status', 'google_id', 'school_id'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'approval_status' => 'string',
    ];

    /**
     * Only `name` is appended — it has no underlying column. `school_id` IS a
     * column (auto-serialized) and `division_id` is a 3-hop chain that should
     * be requested explicitly (or via `with('employee.school.district')`),
     * never auto-fired on every serialization.
     */
    protected $appends = ['name'];

    /**
     * Auto-eager-load `employee` so reads of `$user->name` and the school_id
     * accessor fallback don't trigger a query per access. Costs one extra
     * JOIN-equivalent per User query; pays itself back many times in Filament
     * list pages, navbar widget, and per-request auth.
     */
    protected $with = ['employee'];

    /**
     * @return HasOne<\App\Models\Employee>
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function getNameAttribute(): string
    {
        return $this->employee?->full_name ?? $this->email ?? '';
    }

    public function getSchoolIdAttribute(): ?int
    {
        return $this->attributes['school_id'] ?? $this->employee?->school_id;
    }

    public function getDivisionIdAttribute(): ?int
    {
        return $this->employee?->school?->district?->division_id;
    }

    /**
     * Backward-compat: callsites that did `$user->school` keep working.
     */
    public function getSchoolAttribute(): ?School
    {
        return $this->employee?->school;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->approval_status === 'approved' && $this->roles()->exists();
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }
}
