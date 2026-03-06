<?php
namespace App\Filament\Resources\InternetConnectionResource\Pages;
use App\Filament\Resources\InternetConnectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListInternetConnections extends ListRecords {
    protected static string $resource = InternetConnectionResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
