<?php

namespace App\Models;

use App\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Employee extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        "user_id",
        "school_id",
        "employee_number",
        "first_name",
        "last_name",
        "middle_name",
        "suffix",
        "full_name",
        "position",
        "department",
        "ro_office",
        "sdo_office",
        "employment_type",
        "email",
        "personal_email",
        "mobile_1",
        "mobile_2",
        "date_hired",
        "is_oic",
        "oic_office",
        "is_non_deped_funded",
        "source_of_funds",
        "status",
        "date_of_separation",
        "cause_of_separation",
        "detailed_from",
        "detailed_to",
        "photo",
        "is_inactive",
    ];

    protected $casts = [
        "date_hired" => "date",
        "date_of_separation" => "date",
        "is_oic" => "boolean",
        "is_non_deped_funded" => "boolean",
        "is_inactive" => "boolean",
    ];

    public function getActivitylogOptions(): LogOptions
    {
        // PII columns are logged in `activity_log.properties` JSON if not excluded.
        // A breach of activity_log would expose change history of personal info.
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->logExcept(['personal_email', 'mobile_1', 'mobile_2']);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope());
    }

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($employee) {
            $employee->full_name = trim(
                "{$employee->last_name}, {$employee->first_name}" .
                    ($employee->middle_name
                        ? " {$employee->middle_name}."
                        : "") .
                    ($employee->suffix ? " {$employee->suffix}" : ""),
            );
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\School, self>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\EquipmentAssignment>
     */
    public function equipmentAssignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\EquipmentAssignment>
     */
    public function activeAssignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class)->whereNull("returned_at");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Ticket>
     */
    public function reportedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, "reporter_id");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Ticket>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, "assigned_to_id");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Document>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, "employee_id");
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->full_name} ({$this->employee_number})";
    }

    public function scopeActive($query)
    {
        return $query->where("status", "active");
    }

    public function scopeTeaching($query)
    {
        return $query->where("employment_type", "teaching");
    }

    public function scopeNonTeaching($query)
    {
        return $query->where("employment_type", "non-teaching");
    }
}
