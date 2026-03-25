<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Division Model
 *
 * One Division has many Districts.
 * Hierarchy: Division → District → School
 */
class Division extends Model
{
    protected $fillable = [
        'name',
        'region',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\District, self>
     */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    /**
     * Access to schools through districts
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough<\App\Models\School, \App\Models\District, self>
     */
    public function schools(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(School::class, District::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn(Builder $q) => $q->where('is_active', true));
    }
}
