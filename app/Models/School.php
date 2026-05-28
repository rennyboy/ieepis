<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * School Model
 *
 * Represents a school institution with all its associated data,
 * inventory, employees, documents, and support tickets.
 *
 * @property int $id
 * @property string $name
 * @property string|null $school_code
 * @property string|null $school_id_number
 * @property string|null $governance_level
 * @property string|null $district
 * @property string|null $province
 * @property string|null $city_municipality
 * @property string|null $barangay
 * @property string|null $street
 * @property string|null $complete_address
 * @property string|null $psgc
 * @property string|null $legislative_district
 * @property string|null $region
 * @property string|null $division
 * @property string|null $head_name
 * @property string|null $head_email
 * @property string|null $head_mobile
 * @property string|null $admin_staff_name
 * @property string|null $admin_staff_email
 * @property string|null $admin_staff_mobile
 * @property string|null $landline
 * @property string|null $mobile_1
 * @property string|null $mobile_2
 * @property string|null $email
 * @property string|null $logo
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int|null $travel_time_minutes
 * @property bool $is_very_remote
 * @property bool $is_gidca
 * @property string|null $gidca_type
 * @property string|null $recent_developments
 * @property string|null $status
 * @property int|null $district_id
 * @property int|null $network_administrator_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read string $full_address
 *
 * @method static Builder|static active() Scope to only active schools
 * @method static Builder|static query()
 * @method HasMany employees()
 * @method HasMany equipment()
 * @method HasMany documents()
 * @method HasMany tickets()
 * @method HasMany internetConnections()
 * @method BelongsTo networkAdministrator()
 */
class School extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        "name",
        "school_code",
        "school_id_number",
        "governance_level",
        "district_id",
        "district",
        "province",
        "city_municipality",
        "barangay",
        "street",
        "complete_address",
        "psgc",
        "legislative_district",
        "region",
        "division",
        "head_name",
        "head_email",
        "head_mobile",
        "admin_staff_name",
        "admin_staff_email",
        "admin_staff_mobile",
        "landline",
        "mobile_1",
        "mobile_2",
        "email",
        "logo",
        "latitude",
        "longitude",
        "travel_time_minutes",
        "is_very_remote",
        "is_gidca",
        "recent_developments",
        "status",
        "network_administrator_id",
    ];

    protected $casts = [
        "is_very_remote" => "boolean",
        "latitude" => "float",
        "longitude" => "float",
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    // Relationships
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Employee, self>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Equipment, self>
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Document, self>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Ticket, self>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\InternetConnection, self>
     */
    public function internetConnections(): HasMany
    {
        return $this->hasMany(InternetConnection::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\District, self>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Employee, self>
     */
    public function networkAdministrator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, "network_administrator_id");
    }

    // Scopes
    public function scopeActive($query): Builder
    {
        return $query->where("status", "active");
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        return implode(
            ", ",
            array_filter([
                $this->street,
                $this->barangay,
                $this->city_municipality,
                $this->province,
            ]),
        );
    }

}
