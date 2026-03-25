<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * District Model
 *
 * Represents a school district within a Division.
 * Hierarchy: Division → District → School
 *
 * @property int $id
 * @property string $name
 * @property string|null $division
 * @property string|null $region
 * @property string|null $code
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @method static Builder|static active() Scope to only active districts
 */
class District extends Model
{
    protected $fillable = [
        'name',
        'division_id',
        'division',
        'region',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Division, self>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\School, self>
     */
    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn(Builder $q) => $q->where('is_active', true));
    }
}
