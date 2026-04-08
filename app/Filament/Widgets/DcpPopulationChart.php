<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DcpDistributionData;
use Filament\Widgets\ChartWidget;

class DcpPopulationChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Total ICT Packages and PSI population';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = collect(DcpDistributionData::getData());

        return [
            'datasets' => [
                [
                    'label' => 'Total ICT Packages',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#6366f1', // indigo
                ],
                [
                    'label' => 'PSI Population (Teachers/Employees)',
                    'data' => $data->pluck('psi_pop')->toArray(),
                    'backgroundColor' => '#ec4899', // pink
                ],
            ],
            'labels' => $data->pluck('level')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
