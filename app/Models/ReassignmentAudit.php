<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReassignmentAudit Model
 *
 * Records account reassignment events for auditing purposes.
 *
 * @property int $id
 * @property int $user_id        The user whose account was changed
 * @property int|null $actor_id  The admin user who performed the change
 * @property array $before       JSON payload of attributes before the change
 * @property array $after        JSON payload of attributes after the change
 * @property \\Illuminate\\Support\\Carbon|null $actioned_at
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 *
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|static whereUserId($value)
 *
 * @mixin \\Eloquent
 */
class ReassignmentAudit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * If you prefer a custom table name, change it here.
     */
    protected $table = 'reassignment_audits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'user_id',
        'actor_id',
        'before',
        'after',
        'actioned_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'actioned_at' => 'datetime',
    ];

    /**
     * Get the user whose account was reassigned.
     *
     * @return BelongsTo<User, ReassignmentAudit>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the actor (admin) who performed the reassignment.
     *
     * @return BelongsTo<User, ReassignmentAudit>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Create and persist a reassignment audit record.
     *
     * @param int $userId
     * @param array $before
     * @param array $after
     * @param int|null $actorId
     * @return self
     */
    public static function record(int $userId, array $before, array $after, ?int $actorId = null): self
    {
        return self::create([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'before' => $before,
            'after' => $after,
            'actioned_at' => now(),
        ]);
    }
}
