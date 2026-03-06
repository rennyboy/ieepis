<?php
namespace App\Filament\Resources\InternetConnectionResource\Pages;
use App\Filament\Resources\InternetConnectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditInternetConnection extends EditRecord {
    protected static string $resource = InternetConnectionResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
