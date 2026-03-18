<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use App\Models\School;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EquipmentBySchoolChart extends ChartWidget
{
    protected static ?string $heading = 'Equipment by School';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $schools = School::withCount('equipment')->orderByDesc('equipment_count')->take(8)->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Total Equipment',
                    'data'            => $schools->pluck('equipment_count')->toArray(),
                    'backgroundColor' => [
                        '#1a56db','#0ea5e9','#10b981','#f59e0b',
                        '#ef4444','#8b5cf6','#ec4899','#6b7280',
                    ],
                    'borderRadius'    => 6,
                ],
            ],
            'labels' => $schools->map(fn ($state) => str($state->name)->words(3, '…'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
