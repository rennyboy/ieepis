<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model
 *
 * This model represents system users with role-based access control.
 * Uses Spatie Permission package for role and permission management.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int|null $school_id Foreign key to schools table
 * @property \Carbon\Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * Spatie Permission: Instance Methods
 * @method bool hasRole(string|array $roles) Check if user has one or more roles
 * @method bool hasAnyRole(string|array $roles) Check if user has any of the given roles
 * @method bool hasAllRoles(string|array $roles) Check if user has all of the given roles
 * @method Collection getRoleNames() Get all role names
 * @method void assignRole(string|array|Collection $roles) Assign role(s) to user
 * @method void syncRoles(string|array|Collection $roles) Sync roles for user
 * @method void removeRole(string|array $roles) Remove role(s) from user
 * @method bool hasPermission(string $permission) Check if user has permission
 * @method bool hasAnyPermission(string|array $permissions) Check if user has any permission
 * @method bool hasAllPermissions(string|array $permissions) Check if user has all permissions
 * @method Collection getPermissionsViaRoles() Get all permissions from roles
 * @method void givePermissionTo(string|array|Collection $permissions) Grant permission(s)
 * @method void revokePermissionFor(string|array $permissions) Revoke permission(s)
 * @method void syncPermissions(string|array|Collection $permissions) Sync permissions
 *
 * Spatie Permission: Query Builder Scopes
 * @method static Builder|static role(string|array $roles) Filter users by roles
 * @method static Builder|static permission(string|array $permissions) Filter users by permissions
 *
 * @mixin \Spatie\Permission\Traits\HasRoles
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = ["name", "email", "password", "school_id", "approval_status", "division", "division_id"];
    protected $hidden = ["password", "remember_token"];
    protected $casts = [
        "email_verified_at" => "datetime",
        "password" => "hashed",
        "approval_status" => "string",
    ];

    /**
     * Get the school this user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\School, self>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Determine if the user can access the Filament panel.
     * Blocks pending and rejected users from accessing the system.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Check if the user's account is approved.
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }
}
