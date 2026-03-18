<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use Filament\Widgets\ChartWidget;

class EquipmentConditionChart extends ChartWidget
{
    protected static ?string $heading = 'Equipment Condition';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {

    $conditions = Equipment::select('condition')
    ->selectRaw('COUNT(*) as count')
    ->groupBy('condition')
    ->pluck('count', 'condition');

        return [
            'datasets' => [[
                'data'            => $conditions->values()->toArray(),
                'backgroundColor' => ['#10b981','#f59e0b','#ef4444','#6b7280'],
                'hoverOffset'     => 4,
            ]],
            'labels' => $conditions->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
