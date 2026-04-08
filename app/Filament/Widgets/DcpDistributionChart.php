<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DcpDistributionData;
use Filament\Widgets\ChartWidget;

class DcpDistributionChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'DCP Distribution by School Level';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = collect(DcpDistributionData::getData());

        return [
            'datasets' => [
                [
                    'label' => 'Laptop (Non-Teaching) - L4NT',
                    'data' => $data->pluck('l4nt')->toArray(),
                    'backgroundColor' => '#3b82f6', // blue
                ],
                [
                    'label' => 'Laptop (Teaching) - L4T',
                    'data' => $data->pluck('l4t')->toArray(),
                    'backgroundColor' => '#10b981', // green
                ],
                [
                    'label' => 'Smart TV - STV',
                    'data' => $data->pluck('stv')->toArray(),
                    'backgroundColor' => '#f59e0b', // orange
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
