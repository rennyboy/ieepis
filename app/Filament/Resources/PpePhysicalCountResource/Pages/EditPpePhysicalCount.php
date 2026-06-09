<?php

namespace App\Filament\Resources\PpePhysicalCountResource\Pages;

use App\Filament\Resources\PpePhysicalCountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPpePhysicalCount extends EditRecord
{
    protected static string $resource = PpePhysicalCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        activity('ppe-physical-count')
            ->performedOn($this->record)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'updated'])
            ->log('Updated Physical Count ' . $this->record->count_number);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
