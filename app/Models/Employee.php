<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Employee extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'school_id',
        'employee_number',
        'first_name',
        'last_name',
        'middle_name',
        'suffix',
        'full_name',
        'position',
        'department',
        'ro_office',
        'sdo_office',
        'employment_type',  // teaching / non-teaching
        'email',
        'personal_email',
        'mobile_1',
        'mobile_2',
        'date_hired',
        'is_oic',
        'oic_office',
        'is_non_deped_funded',
        'source_of_funds',
        'status',           // active / inactive / retired
        'date_of_separation',
        'cause_of_separation',
        'detailed_from',
        'detailed_to',
        'photo',
        'is_inactive',
    ];

    protected $casts = [
        'date_hired'        => 'date',
        'date_of_separation'=> 'date',
        'is_oic'            => 'boolean',
        'is_non_deped_funded'=> 'boolean',
        'is_inactive'       => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($employee) {
            $employee->full_name = trim(
                "{$employee->last_name}, {$employee->first_name}"
                . ($employee->middle_name ? " {$employee->middle_name}." : '')
                . ($employee->suffix ? " {$employee->suffix}" : '')
            );
        });
    }

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function equipmentAssignments()
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    public function activeAssignments()
    {
        return $this->hasMany(EquipmentAssignment::class)->whereNull('returned_at');
    }

    public function reportedTickets()
    {
        return $this->hasMany(Ticket::class, 'reporter_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTeaching($query)
    {
        return $query->where('employment_type', 'teaching');
    }

    public function scopeNonTeaching($query)
    {
        return $query->where('employment_type', 'non-teaching');
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return "{$this->full_name} ({$this->employee_number})";
    }

    public function getCurrentEquipmentCountAttribute(): int
    {
        return $this->activeAssignments()->count();
    }
}
