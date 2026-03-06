<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class School extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'school_code',
        'school_id_number',
        'governance_level',
        'district',
        'province',
        'city_municipality',
        'barangay',
        'street',
        'complete_address',
        'psgc',
        'legislative_district',
        'region',
        'division',
        'head_name',
        'head_email',
        'head_mobile',
        'admin_staff_name',
        'admin_staff_email',
        'admin_staff_mobile',
        'landline',
        'mobile_1',
        'mobile_2',
        'email',
        'logo',
        'latitude',
        'longitude',
        'travel_time_minutes',
        'is_very_remote',
        'is_gidca',
        'gidca_type',
        'recent_developments',
        'status',
        'network_administrator_id',
    ];

    protected $casts = [
        'is_very_remote' => 'boolean',
        'latitude'        => 'float',
        'longitude'       => 'float',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    // Relationships
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function internetConnections()
    {
        return $this->hasMany(InternetConnection::class);
    }

    public function networkAdministrator()
    {
        return $this->belongsTo(Employee::class, 'network_administrator_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->street,
            $this->barangay,
            $this->city_municipality,
            $this->province,
        ]));
    }

    public function getEquipmentCountAttribute(): int
    {
        return $this->equipment()->count();
    }

    public function getAssignedEquipmentCountAttribute(): int
    {
        return $this->equipment()->where('accountability_status', 'assigned')->count();
    }
}
