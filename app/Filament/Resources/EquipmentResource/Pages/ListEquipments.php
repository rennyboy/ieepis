<?php

namespace App\Filament\Resources\EquipmentResource\Pages;

use App\Filament\Pages\OfflineEquipmentPage;
use App\Filament\Resources\EquipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipments extends ListRecords
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('offline')
                ->label('Offline Mode')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('gray')
                ->url(OfflineEquipmentPage::getUrl()),
            Actions\CreateAction::make(),
        ];
    }
}
