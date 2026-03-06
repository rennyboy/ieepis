<?php

namespace App\Providers\Filament;

use App\Filament\Resources\SchoolResource;
use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\EquipmentResource;
use App\Filament\Resources\AssignmentResource;
use App\Filament\Resources\DocumentResource;
use App\Filament\Resources\TicketResource;
use App\Filament\Resources\InternetConnectionResource;
use App\Filament\Widgets\IEEPISStatsOverview;
use App\Filament\Widgets\EquipmentBySchoolChart;
use App\Filament\Widgets\EquipmentConditionChart;
use App\Filament\Widgets\LatestTicketsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
                'danger'  => Color::Red,
                'warning' => Color::Amber,
                'success' => Color::Emerald,
                'info'    => Color::Cyan,
                'gray'    => Color::Slate,
            ])
            ->brandName('IEEPIS')
            ->brandLogo(asset('images/ieepis-logo.png'))
            ->favicon(asset('images/ieepis-favicon.png'))
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Overview'),
                NavigationGroup::make('Management')
                    ->icon('heroicon-o-building-office'),
                NavigationGroup::make('ICT Inventory')
                    ->icon('heroicon-o-computer-desktop'),
                NavigationGroup::make('Monitoring')
                    ->icon('heroicon-o-chart-bar'),
                NavigationGroup::make('Reports & Tools')
                    ->icon('heroicon-o-document-chart-bar')
                    ->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                IEEPISStatsOverview::class,
                EquipmentBySchoolChart::class,
                EquipmentConditionChart::class,
                LatestTicketsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()->label('My Profile'),
            ]);
    }
}
