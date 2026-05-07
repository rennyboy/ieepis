<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Enums\DocumentType;
use App\Filament\Resources\AssignmentResource;
use App\Models\EquipmentAssignment;
use App\Services\AssignmentDocumentWriter;
use App\Services\AssignmentService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class EditAssignment extends EditRecord
{
    protected static string $resource = AssignmentResource::class;

    private ?string $pendingSupportingFile = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $value = $data['supporting_doc_file'] ?? null;
        unset($data['supporting_doc_file']);

        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        $this->pendingSupportingFile = is_string($value) && $value !== '' ? $value : null;

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->pendingSupportingFile === null) {
            return;
        }

        /** @var EquipmentAssignment $record */
        $record = $this->record;

        $type = $record->supporting_doc_type
            ? DocumentType::tryFrom($record->supporting_doc_type) ?? DocumentType::ICS
            : DocumentType::ICS;

        app(AssignmentDocumentWriter::class)->write(
            $record->loadMissing('equipment'),
            $this->pendingSupportingFile,
            $type,
            Auth::user(),
        );
    }

    protected function getHeaderActions(): array
    {
        /** @var EquipmentAssignment $record */
        $record = $this->record;

        return [
            Actions\Action::make('return')
                ->label('Return Equipment')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn () => $record->isActive())
                ->form([
                    Forms\Components\DatePicker::make('returned_at')
                        ->label('Date Returned')
                        ->required()
                        ->default(now()),
                    Forms\Components\Textarea::make('notes')
                        ->label('Return Notes')
                        ->rows(3),
                    Forms\Components\FileUpload::make('return_doc_file')
                        ->label('Return Receipt (PDF / Image)')
                        ->helperText('Optional. Attach the signed RRSP / return slip — PDF or photo, up to 10 MB.')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                        ->directory(fn () => 'schools/' . ($record->school_id ?? 'general') . '/assignments/returns')
                        ->maxSize(10240)
                        ->openable()
                        ->downloadable()
                        ->previewable(),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) use ($record): void {
                    $returnFile = $data['return_doc_file'] ?? null;
                    unset($data['return_doc_file']);

                    if (is_array($returnFile)) {
                        $returnFile = reset($returnFile) ?: null;
                    }

                    try {
                        app(AssignmentService::class)->return($record, $data, Auth::user());

                        if (is_string($returnFile) && $returnFile !== '') {
                            app(AssignmentDocumentWriter::class)->write(
                                $record->loadMissing('equipment'),
                                $returnFile,
                                DocumentType::RRSP,
                                Auth::user(),
                            );
                        }

                        Notification::make()->title('Equipment returned')->success()->send();
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (RuntimeException $e) {
                        Notification::make()
                            ->title('Cannot return equipment')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
