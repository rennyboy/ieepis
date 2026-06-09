<?php

namespace App\Models;

use App\Enums\PhysicalCountStatus;
use App\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * PpePhysicalCount — header record for a physical count session.
 *
 * @property int $id
 * @property string $count_number
 * @property \Carbon\Carbon $inventory_date
 * @property string|null $inventory_period
 * @property int $school_id
 * @property string|null $location
 * @property int|null $conducted_by
 * @property int|null $verified_by
 * @property PhysicalCountStatus $status
 * @property string|null $remarks
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read float $total_shortage_value
 * @property-read float $total_overage_value
 * @property-read int $total_items_count
 */
class PpePhysicalCount extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'ppe_physical_counts';

    protected $fillable = [
        'count_number',
        'inventory_date',
        'inventory_period',
        'school_id',
        'location',
        'conducted_by',
        'verified_by',
        'status',
        'remarks',
    ];

    protected $casts = [
        'inventory_date' => 'date',
        'status' => PhysicalCountStatus::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope());

        static::creating(function (self $model): void {
            if (empty($model->count_number)) {
                $model->count_number = self::generateCountNumber();
            }
        });
    }

    /**
     * Generate a sequential count number in the format PC-YYYY-XXXX.
     */
    private static function generateCountNumber(): string
    {
        $year = now()->year;
        $prefix = "PC-{$year}-";

        $last = static::withoutGlobalScopes()
            ->where('count_number', 'like', "{$prefix}%")
            ->orderByDesc('count_number')
            ->value('count_number');

        $sequence = 1;

        if ($last) {
            $lastSequence = (int) str_replace($prefix, '', $last);
            $sequence = $lastSequence + 1;
        }

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    // ─── Relationships ───────────────────────────────────────────

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\School, self>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Employee, self>
     */
    public function conductedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'conducted_by');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Employee, self>
     */
    public function verifiedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'verified_by');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\PpePhysicalCountItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PpePhysicalCountItem::class, 'physical_count_id');
    }

    // ─── Computed Accessors ──────────────────────────────────────

    public function getTotalShortageValueAttribute(): float
    {
        return (float) $this->items()->sum('shortage_value');
    }

    public function getTotalOverageValueAttribute(): float
    {
        return (float) $this->items()->sum('overage_value');
    }

    public function getTotalItemsCountAttribute(): int
    {
        return (int) $this->items()->count();
    }
}
