<?php

namespace App\Filament\Resources\EquipmentResource\Pages;

use App\Filament\Pages\OfflineEquipmentPage;
use App\Filament\Resources\EquipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListEquipments extends ListRecords
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadUnresolved')
                ->label('Download Unresolved CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->visible(fn () => $this->getLatestUnresolvedFilename() !== null)
                ->url(fn () => route('equipment.excel.unresolved', [
                    'filename' => $this->getLatestUnresolvedFilename(),
                ])),
            Actions\Action::make('offline')
                ->label('Offline Mode')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('gray')
                ->url(OfflineEquipmentPage::getUrl()),
            Actions\CreateAction::make(),
        ];
    }

    private function getLatestUnresolvedFilename(): ?string
    {
        $files = Storage::disk('local')->files('imports/unresolved');

        if ($files === []) {
            return null;
        }

        rsort($files);

        return basename($files[0]);
    }
}
