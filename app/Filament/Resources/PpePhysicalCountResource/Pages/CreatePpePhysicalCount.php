<?php

namespace App\Filament\Resources\PpePhysicalCountResource\Pages;

use App\Filament\Resources\PpePhysicalCountResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePpePhysicalCount extends CreateRecord
{
    protected static string $resource = PpePhysicalCountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Items are handled by the Repeater relationship, so we don't need
        // to route through the service for the initial create. The model's
        // boot hooks and the Repeater's relationship handling take care of it.
        return $data;
    }

    protected function afterCreate(): void
    {
        activity('ppe-physical-count')
            ->performedOn($this->record)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'created'])
            ->log('Created Physical Count ' . $this->record->count_number);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
