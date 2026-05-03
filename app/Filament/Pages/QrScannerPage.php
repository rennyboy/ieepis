<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\LivewireComponent;

class QrScannerPage extends Page
{
    protected static string $view = 'filament.pages.qr-scanner-page';

    // Navigation
    protected static ?string $navigationLabel = 'QR Scanner';
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationGroup = 'Tools';

    // Permission
    protected static function canView(): bool
    {
        return auth()->user()?->can('scan qr');
    }

    public function getTitle(): string
    {
        return 'QR Code Scanner';
    }
}
