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
        $user = auth()->user();
        
        // Super admin can always view (explicit check to avoid gate issues)
        if ($user && $user->hasRole('super-admin')) {
            return true;
        }
        
        // Check for scan qr permission for other roles
        return $user?->can('scan qr') ?? false;
    }

    public function getTitle(): string
    {
        return 'QR Code Scanner';
    }
}
