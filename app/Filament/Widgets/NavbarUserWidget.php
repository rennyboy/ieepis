<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class NavbarUserWidget extends Widget
{
    protected static ?string $heading = null;
    protected static string $view = 'filament.widgets.navbar-user-widget';
    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        $user = Auth::user();

        return [
            'user' => $user,
            'userName' => $user->name,
            'userRole' => $user->getRoleNames()->first() ?? 'User',
            'schoolName' => $user->school?->name ?? null,
        ];
    }
}
