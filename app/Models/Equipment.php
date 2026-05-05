<?php

namespace App\Models;

use App\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Enums\AccountabilityStatus;
use App\Enums\EquipmentCondition;
use App\Enums\TransactionType;

class Equipment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        "school_id",
        "document_id",
        "property_no",
        "old_property_no",
        "serial_number",
        "item_type",
        "equipment_type",
        "brand",
        "model",
        "specifications",
        "unit_of_measure",
        "category",
        "classification",
        "gl_sl_code",
        "uacs_code",
        "is_dcp",
        "dcp_package",
        "dcp_year",
        "is_non_dcp",
        "acquisition_cost",
        "acquisition_date",
        "received_date",
        "estimated_useful_life",
        "mode_of_acquisition",
        "source_of_acquisition",
        "donor",
        "source_of_funds",
        "pmp_reference_no",
        "supporting_doc_type_acquisition",
        "supporting_doc_no_acquisition",
        "supplier",
        "under_warranty",
        "warranty_end_date",
        "equipment_location",
        "is_functional",
        "condition",
        "accountability_status",
        "disposition_status",
        "remarks",
        "qr_code",
        "transaction_type",
        "supporting_doc_type_issuance",
        "supporting_doc_no_issuance",
    ];

    protected $casts = [
        "acquisition_date" => "date",
        "received_date" => "date",
        "warranty_end_date" => "date",
        "acquisition_cost" => "decimal:2",
        "is_dcp" => "boolean",
        "is_non_dcp" => "boolean",
        "under_warranty" => "boolean",
        "is_functional" => "boolean",
        "accountability_status" => AccountabilityStatus::class,
        "condition" => EquipmentCondition::class,
        "transaction_type" => TransactionType::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope());
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($equipment) {
            $equipment->qr_code = self::generateQrPayload($equipment);
        });
        static::updating(function ($equipment) {
            if ($equipment->isDirty(["property_no", "serial_number"])) {
                $equipment->qr_code = self::generateQrPayload($equipment);
            }
        });
    }

    private static function generateQrPayload($equipment): string
    {
        return "IEEPIS|{$equipment->property_no}|{$equipment->serial_number}|{$equipment->brand} {$equipment->model}";
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\School, self>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Document, self>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\EquipmentAssignment>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\EquipmentAssignment>
     */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(EquipmentAssignment::class)
            ->where(fn ($q) => $q->whereNull("returned_at"))
            ->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Document>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Most-recent document with a file attached to this equipment, ordered by document_date then created_at.
     * Returns null when no documents with files exist.
     */
    public function sharedDocument(): ?Document
    {
        return $this->documents()
            ->whereNotNull('file_path')
            ->orderByDesc("document_date")
            ->orderByDesc("created_at")
            ->first();
    }

    public function hasSharedDocument(): bool
    {
        return $this->documents()->whereNotNull('file_path')->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Ticket>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Ticket>
     */
    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(Ticket::class)->where(fn ($q) => $q->where("ticket_type", "Maintenance"));
    }

    public function scopeAssigned($query)
    {
        return $query->where(["accountability_status" => "assigned"]);
    }

    public function scopeUnassigned($query)
    {
        return $query->where(["accountability_status" => "unassigned"]);
    }

    public function scopeDcp($query)
    {
        return $query->where(["is_dcp" => true]);
    }

    public function scopeFunctional($query)
    {
        return $query->where(["is_functional" => true]);
    }

    public function scopeForDisposal($query)
    {
        return $query->where(["accountability_status" => "For Disposal"]);
    }

    public function getCurrentAccountableAttribute(): ?Employee
    {
        return $this->activeAssignment?->employee;
    }

    public function getWarrantyStatusAttribute(): string
    {
        if (!$this->under_warranty || !$this->warranty_end_date) {
            return "No Warranty";
        }
        return now()->lt($this->warranty_end_date) ? "Active" : "Expired";
    }
}
