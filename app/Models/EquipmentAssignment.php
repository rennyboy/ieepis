<?php

namespace App\Models;

use App\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * EquipmentAssignment Model
 *
 * @property int $id
 * @property int $school_id
 * @property int $equipment_id
 * @property int $employee_id
 * @property int|null $new_accountable_id
 * @property int|null $custodian_id
 * @property \Carbon\Carbon $assigned_at
 * @property \Carbon\Carbon|null $custodian_received_at
 * @property \Carbon\Carbon|null $returned_at
 * @property \Carbon\Carbon|null $new_accountable_received_at
 * @property string|null $assigned_by
 * @property string $transaction_type
 * @property string|null $supporting_doc_type
 * @property string|null $supporting_doc_no
 * @property string|null $notes
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @method BelongsTo school()
 * @method BelongsTo equipment()
 * @method BelongsTo employee()
 * @method BelongsTo newAccountable()
 * @method BelongsTo custodian()
 */
class EquipmentAssignment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        "school_id",
        "equipment_id",
        "employee_id",
        "new_accountable_id",
        "custodian_id",
        "assigned_at",
        "custodian_received_at",
        "returned_at",
        "new_accountable_received_at",
        "assigned_by",
        "transaction_type",
        "supporting_doc_type",
        "supporting_doc_no",
        "notes",
        "is_active",
    ];

    protected $casts = [
        "assigned_at" => "date",
        "custodian_received_at" => "date",
        "returned_at" => "date",
        "new_accountable_received_at" => "date",
        "is_active" => "boolean",
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
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
    public function newAccountable(): BelongsTo
    {
        return $this->belongsTo(Employee::class, "new_accountable_id");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Employee, self>
     */
    public function custodian(): BelongsTo
    {
        return $this->belongsTo(Employee::class, "custodian_id");
    }
}
