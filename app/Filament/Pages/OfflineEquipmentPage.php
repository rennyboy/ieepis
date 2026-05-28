<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class OfflineEquipmentPage extends Page
{
    protected static string $view = 'filament.pages.offline-equipment-page';

    protected static ?string $navigationLabel = 'Equipment (Offline)';
    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-down';
    protected static ?string $navigationGroup = 'ICT Inventory';
    protected static ?int $navigationSort = 20;

    protected static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->hasAnyRole(['super-admin', 'sdo-admin', 'school-admin', 'technician']);
    }

    public function getTitle(): string
    {
        return 'Offline Equipment';
    }
}
