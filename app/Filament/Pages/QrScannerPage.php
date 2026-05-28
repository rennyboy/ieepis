<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;

class QrScannerPage extends Page
{
    protected static string $view = 'filament.pages.qr-scanner-page';

    // Navigation
    protected static ?string $navigationLabel = 'QR Scanner';
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationGroup = 'ICT Inventory';

    // Permission
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user?->hasRole('super-admin')) {
            return true;
        }

        return $user?->can('scan qr') ?? false;
    }

    public function getTitle(): string
    {
        return 'QR Code Scanner';
    }
}
