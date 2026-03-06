<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Equipment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'school_id',
        'property_no',
        'old_property_no',
        'serial_number',
        'item_type',         // Device Type / Equipment / Hardware / Software / Peripherals
        'equipment_type',    // Laptop, Desktop, Printer, etc.
        'brand',
        'model',
        'specifications',
        'unit_of_measure',
        'category',          // High-Value / Low-Value
        'classification',    // Machinery and Equipment for ICT
        'gl_sl_code',
        'uacs_code',
        'is_dcp',
        'dcp_package',
        'dcp_year',
        'is_non_dcp',
        'acquisition_cost',
        'acquisition_date',
        'received_date',
        'estimated_useful_life',
        'mode_of_acquisition', // Purchased / Donation / Grant
        'source_of_acquisition',
        'donor',
        'source_of_funds',
        'pmp_reference_no',
        'supporting_doc_type_acquisition',
        'supporting_doc_no_acquisition',
        'supplier',
        'under_warranty',
        'warranty_end_date',
        'equipment_location',
        'is_functional',
        'condition',         // Good / Fair / Poor
        'accountability_status', // Normal / Transferred / Stolen / Lost / Damaged / For Disposal
        'disposition_status',
        'remarks',
        'qr_code',
        'transaction_type',
        'supporting_doc_type_issuance',
        'supporting_doc_no_issuance',
    ];

    protected $casts = [
        'acquisition_date'     => 'date',
        'received_date'        => 'date',
        'warranty_end_date'    => 'date',
        'acquisition_cost'     => 'decimal:2',
        'is_dcp'               => 'boolean',
        'is_non_dcp'           => 'boolean',
        'under_warranty'       => 'boolean',
        'is_functional'        => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($equipment) {
            $equipment->qr_code = self::generateQrPayload($equipment);
        });
        static::updating(function ($equipment) {
            if ($equipment->isDirty(['property_no', 'serial_number'])) {
                $equipment->qr_code = self::generateQrPayload($equipment);
            }
        });
    }

    private static function generateQrPayload($equipment): string
    {
        return "IEEPIS|{$equipment->property_no}|{$equipment->serial_number}|{$equipment->brand} {$equipment->model}";
    }

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function assignments()
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    public function activeAssignment()
    {
        return $this->hasOne(EquipmentAssignment::class)->whereNull('returned_at')->latest();
    }

    public function accountableOfficer()
    {
        return $this->hasOneThrough(
            Employee::class,
            EquipmentAssignment::class,
            'equipment_id',
            'id',
            'id',
            'employee_id'
        );
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // Scopes
    public function scopeAssigned($query)
    {
        return $query->where('accountability_status', 'assigned');
    }

    public function scopeUnassigned($query)
    {
        return $query->where('accountability_status', 'unassigned');
    }

    public function scopeDcp($query)
    {
        return $query->where('is_dcp', true);
    }

    public function scopeFunctional($query)
    {
        return $query->where('is_functional', true);
    }

    public function scopeForDisposal($query)
    {
        return $query->where('accountability_status', 'For Disposal');
    }

    // Accessors
    public function getCurrentAccountableAttribute(): ?Employee
    {
        return $this->activeAssignment?->employee;
    }

    public function getWarrantyStatusAttribute(): string
    {
        if (!$this->under_warranty || !$this->warranty_end_date) return 'No Warranty';
        return now()->lt($this->warranty_end_date) ? 'Active' : 'Expired';
    }
}
