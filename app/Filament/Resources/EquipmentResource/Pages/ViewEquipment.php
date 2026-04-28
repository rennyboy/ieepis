<?php

namespace App\Filament\Resources\EquipmentResource\Pages;

use App\Filament\Resources\EquipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEquipment extends ViewRecord
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EquipmentResource::sharedDocumentViewPageAction(),
            EquipmentResource::sharedDocumentAttachPageAction(),
            Actions\EditAction::make(),
        ];
    }
}
