<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DcpStatsOverview;
use App\Filament\Widgets\DcpDistributionChart;
use App\Filament\Widgets\DcpPopulationChart;
use App\Filament\Widgets\DcpPercentagesTable;
use App\Filament\Widgets\DcpMaintenanceHistory;

class DcpDashboard extends Page
{
    protected static ?string $slug = "dcp-dashboard";

    protected static ?string $navigationIcon = "heroicon-o-presentation-chart-line";

    protected static ?string $navigationLabel = "DCP Distribution";

    protected static ?string $title = "DCP Distribution Dashboard";

    protected static ?string $navigationGroup = "Overview";

    protected static ?int $navigationSort = 2;

    protected static string $view = "filament.pages.dcp-dashboard";

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\TotalSchoolsWidget::class,
            DcpStatsOverview::class,
            DcpDistributionChart::class,
            DcpPopulationChart::class,
            DcpPercentagesTable::class,
            DcpMaintenanceHistory::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
