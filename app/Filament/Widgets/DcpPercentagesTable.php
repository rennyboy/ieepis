<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DcpDistributionData;
use Filament\Widgets\Widget;

class DcpPercentagesTable extends Widget
{
    protected static ?int $sort = 3;

    protected static bool $isDiscovered = false;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.dcp-percentages-table';

    protected function getViewData(): array
    {
        return [
            'data' => DcpDistributionData::getData(),
        ];
    }
}
