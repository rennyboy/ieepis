<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\EquipmentAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Persists FileUpload artifacts from the Assignment forms as Document rows
 * keyed on (equipment_assignment_id, document_type) so re-uploads replace cleanly.
 *
 * The Filament FileUpload component already moves the file to the configured
 * disk before this runs, so $filePath is the on-disk path relative to the
 * 'public' disk root (e.g. "schools/3/assignments/abc.pdf").
 */
class AssignmentDocumentWriter
{
    public function write(
        EquipmentAssignment $assignment,
        ?string $filePath,
        DocumentType $type,
        User $actor,
    ): ?Document {
        if (blank($filePath)) {
            return null;
        }

        $disk = Storage::disk('public');
        $exists = $disk->exists($filePath);

        $title = sprintf(
            '%s – %s',
            $type->value,
            $assignment->equipment?->property_no ?? "Assignment #{$assignment->id}",
        );

        return Document::updateOrCreate(
            [
                'equipment_assignment_id' => $assignment->id,
                'document_type' => $type,
            ],
            [
                'school_id' => $assignment->school_id,
                'equipment_id' => $assignment->equipment_id,
                'employee_id' => $assignment->employee_id,
                'document_no' => $assignment->supporting_doc_no,
                'title' => $title,
                'file_path' => $filePath,
                'file_name' => basename($filePath),
                'file_size' => $exists ? $disk->size($filePath) : null,
                'mime_type' => $exists ? $disk->mimeType($filePath) : null,
                'uploaded_by_id' => $actor->id,
                'document_date' => $assignment->assigned_at,
            ],
        );
    }
}
