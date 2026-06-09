<?php

namespace App\Filament\Resources\PpePhysicalCountResource\Pages;

use App\Filament\Resources\PpePhysicalCountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPpePhysicalCounts extends ListRecords
{
    protected static string $resource = PpePhysicalCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
