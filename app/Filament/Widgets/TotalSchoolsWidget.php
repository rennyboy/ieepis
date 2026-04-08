<?php

namespace App\Filament\Widgets;

use App\Models\School;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalSchoolsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make("Total Schools", School::count())
                ->description("Total registered schools")
                ->descriptionIcon("heroicon-m-academic-cap")
                ->color("success"),
        ];
    }
}
