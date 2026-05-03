<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Register;
use App\Filament\Widgets\EquipmentBySchoolChart;
use App\Filament\Widgets\EquipmentConditionChart;
use App\Filament\Widgets\IEEPISStatsOverview;
use App\Filament\Widgets\LatestTicketsWidget;
use App\Http\Middleware\EnsureAccountIsApproved;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
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
            ->registration(Register::class)
            ->profile(\App\Filament\Pages\Auth\EditProfile::class, isSimple: false)
            ->colors([
                'primary' => [
                    50 => '#f0fdf4',
                    100 => '#dcfce7',
                    200 => '#bbf7d0',
                    300 => '#86efac',
                    400 => '#4ade80',
                    500 => '#22c55e',
                    600 => '#16a34a', // Primary Green
                    700 => '#15803d',
                    800 => '#166534',
                    900 => '#14532d', // Deep Accent Green
                    950 => '#052e16',
                ],
                'danger' => Color::Red,
                'warning' => Color::Amber,
                'success' => Color::Emerald,
                'info' => Color::Cyan,
                'gray' => [
                    50 => '#f5f5f2', // Warm Soft White (Light Theme Background)
                    100 => '#e7f5ea', // Green tinted white (Dark Theme Texts)
                    200 => '#d1d5d2',
                    300 => '#b6bbb8',
                    400 => '#9da29f',
                    500 => '#808784',
                    600 => '#616a66',
                    700 => '#444c48',
                    800 => '#28302c',
                    900 => '#111814', // Elegant Surface (Dark Theme)
                    950 => '#0a0f0c', // Elegant Background (Dark Theme)
                ],
            ])
            ->brandLogo(fn() => view('filament.components.brand-logo'))
            ->favicon(asset('images/ieepis-favicon.png'))
            // ✅ Configure for full-width layout with vertical sidebar
            ->sidebarCollapsibleOnDesktop(true)
            ->topNavigation(false)
            ->navigationGroups([
                NavigationGroup::make('Overview')->icon('heroicon-o-chart-pie'),
                NavigationGroup::make('ICT Inventory')->icon('heroicon-o-computer-desktop'),
                NavigationGroup::make('People')->icon('heroicon-o-users'),
                NavigationGroup::make('Organization')->icon('heroicon-o-building-office-2'),
                NavigationGroup::make('Documents & Tickets')->icon('heroicon-o-inbox-stack'),
                NavigationGroup::make('Administration')
                    ->icon('heroicon-o-shield-check')
                    ->collapsed(),
            ])
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages',
            )
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\DcpDashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\\Filament\\Widgets',
            )
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
                EnsureAccountIsApproved::class,
            ])
            ->databaseNotifications()
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            // Filament's `->profile()` adds the "My Profile" link to the user
            // menu automatically, so no manual entry needed here.
            ->renderHook(
                'panels::auth.login.after',
                fn() => view('auth.google-button-login'),
            )
            ->renderHook(
                'panels::auth.login.form.after',
                fn() => view('auth.google-button-login'),
            )
            ->renderHook(
                'panels::auth.register.after',
                fn() => view('auth.google-button-register'),
            )
            ->renderHook(
                'panels::auth.register.form.after',
                fn() => view('auth.google-button-register'),
            )
            // ✅ Keep render hook for user info in minimal top bar
            ->renderHook(
                'panels::topbar.end',
                fn() => view('filament.widgets.navbar-user-widget', [
                    'userName' => Auth::user()?->name ?? 'User',
                    'userRole' => Auth::user()?->getRoleNames()->first() ?? 'User',
                    'schoolName' => Auth::user()?->school?->name ?? null,
                ]),
            )
            // ✅ PWA Meta Tags and Service Worker
            ->renderHook(
                'panels::head.end',
                fn() => view('filament.pwa-head'),
            );
    }
}
