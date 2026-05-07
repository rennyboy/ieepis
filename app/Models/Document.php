<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document Model
 *
 * @property int $id
 * @property int $school_id
 * @property int|null $equipment_id
 * @property int|null $employee_id
 * @property int|null $equipment_assignment_id
 * @property string $document_type
 * @property string|null $document_no
 * @property string $title
 * @property string|null $description
 * @property string $file_path
 * @property string|null $file_name
 * @property int|null $file_size
 * @property string|null $mime_type
 * @property int|null $uploaded_by_id
 * @property \Carbon\Carbon|null $document_date
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read string $file_size_human
 * @property-read string $file_url
 *
 * @method BelongsTo school()
 * @method BelongsTo equipment()
 * @method BelongsTo employee()
 * @method BelongsTo equipmentAssignment()
 * @method BelongsTo uploadedBy()
 */
class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "school_id",
        "equipment_id",
        "employee_id",
        "equipment_assignment_id",
        "document_type",
        "document_no",
        "title",
        "description",
        "file_path",
        "file_name",
        "file_size",
        "mime_type",
        "uploaded_by_id",
        "document_date",
    ];

    protected $casts = [
        "document_date" => "date",
        "document_type" => DocumentType::class,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope());
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\EquipmentAssignment, self>
     */
    public function equipmentAssignment(): BelongsTo
    {
        return $this->belongsTo(EquipmentAssignment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, self>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, "uploaded_by_id");
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . " MB";
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . " KB";
        }
        return $bytes . " B";
    }

    public function getFileUrlAttribute(): string
    {
        return asset("storage/" . $this->file_path);
    }
}
