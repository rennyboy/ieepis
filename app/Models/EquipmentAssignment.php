<?php

namespace App\Models;

use App\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * EquipmentAssignment Model
 *
 * @property int $id
 * @property int $school_id
 * @property int $equipment_id
 * @property int $employee_id
 * @property int|null $custodian_id
 * @property int|null $assigned_by_id
 * @property \Carbon\Carbon $assigned_at
 * @property \Carbon\Carbon|null $custodian_received_at
 * @property \Carbon\Carbon|null $returned_at
 * @property string $transaction_type
 * @property string|null $supporting_doc_type
 * @property string|null $supporting_doc_no
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @method BelongsTo school()
 * @method BelongsTo equipment()
 * @method BelongsTo employee()
 * @method BelongsTo custodian()
 * @method BelongsTo assignedBy()
 */
class EquipmentAssignment extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'school_id',
        'equipment_id',
        'employee_id',
        'custodian_id',
        'assigned_by_id',
        'assigned_at',
        'custodian_received_at',
        'returned_at',
        'transaction_type',
        'supporting_doc_type',
        'supporting_doc_no',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'date',
        'custodian_received_at' => 'date',
        'returned_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function isActive(): bool
    {
        return $this->returned_at === null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\School, self>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Equipment, self>
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Employee, self>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Employee, self>
     */
    public function custodian(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'custodian_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, self>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }
}
