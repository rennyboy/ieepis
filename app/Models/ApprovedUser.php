<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * ApprovedUser Model — Email Whitelist / Registry
 *
 * Controls who is allowed to register and log in to the system.
 * Super Admin and Division Admin manage this list.
 *
 * @property int $id
 * @property string $email
 * @property string|null $name
 * @property string|null $role
 * @property string|null $division
 * @property int|null $division_id
 * @property string $status  pending|approved|rejected
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $actioned_at
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @method static Builder|static pending()
 * @method static Builder|static approved()
 */
class ApprovedUser extends Model
{
    protected $table = 'approved_users';

    protected $fillable = [
        'email',
        'role',
        'school_id',
        'status',
        'approved_by',
        'actioned_at',
        'notes',
    ];

    protected $casts = [
        'actioned_at' => 'datetime',
    ];

    public function scopePending(Builder $query): Builder
    {
        return $query->where(fn(Builder $q) => $q->where('status', 'pending'));
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where(fn(Builder $q) => $q->where('status', 'approved'));
    }

    public function actionedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function school(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
