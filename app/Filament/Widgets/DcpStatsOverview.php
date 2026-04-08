<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DcpDistributionData;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DcpStatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $totals = DcpDistributionData::getTotals();

        return [
            Stat::make('Total L4T', $totals['l4t'])
                ->description('Laptops for Teaching')
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('success'),
            Stat::make('Total L4NT', $totals['l4nt'])
                ->description('Laptops for Non-Teaching')
                ->descriptionIcon('heroicon-m-device-tablet')
                ->color('info'),
            Stat::make('Total Smart TV', $totals['stv'])
                ->description('SmartTV Packages')
                ->descriptionIcon('heroicon-m-tv')
                ->color('warning'),
            Stat::make('Overall PSI Population', $totals['psi_pop'])
                ->description('Total Teachers & Employees')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}
