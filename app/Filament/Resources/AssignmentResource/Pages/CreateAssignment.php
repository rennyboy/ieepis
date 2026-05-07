<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Enums\DocumentType;
use App\Filament\Resources\AssignmentResource;
use App\Models\EquipmentAssignment;
use App\Services\AssignmentDocumentWriter;
use App\Services\AssignmentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $supportingFile = $this->extractSupportingFile($data);

        try {
            /** @var EquipmentAssignment $assignment */
            $assignment = app(AssignmentService::class)->issue($data, Auth::user());

            if ($supportingFile !== null) {
                $type = $assignment->supporting_doc_type
                    ? DocumentType::tryFrom($assignment->supporting_doc_type) ?? DocumentType::ICS
                    : DocumentType::ICS;

                app(AssignmentDocumentWriter::class)->write(
                    $assignment->loadMissing('equipment'),
                    $supportingFile,
                    $type,
                    Auth::user(),
                );
            }

            return $assignment;
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('Cannot create assignment')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    private function extractSupportingFile(array &$data): ?string
    {
        $value = $data['supporting_doc_file'] ?? null;
        unset($data['supporting_doc_file']);

        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }
}
