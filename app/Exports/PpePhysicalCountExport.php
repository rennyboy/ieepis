<?php

namespace App\Exports;

use App\Models\PpePhysicalCount;
use App\Models\PpePhysicalCountItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PpePhysicalCountExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private readonly PpePhysicalCount $physicalCount,
    ) {}

    public function collection()
    {
        return $this->physicalCount->items()->get();
    }

    public function headings(): array
    {
        return [
            'Article',
            'Description',
            'Property Number',
            'Unit of Measure',
            'Unit Value',
            'Qty per Property Card',
            'Qty per Physical Count',
            'Shortage Quantity',
            'Shortage Value',
            'Overage Quantity',
            'Overage Value',
            'Remarks',
        ];
    }

    public function map($item): array
    {
        return [
            $item->article,
            $item->description,
            $item->property_number,
            $item->unit_of_measure,
            $item->unit_value,
            $item->quantity_property_card,
            $item->quantity_physical_count,
            $item->shortage_quantity,
            $item->shortage_value,
            $item->overage_quantity,
            $item->overage_value,
            $item->remarks,
        ];
    }

    public function title(): string
    {
        return 'Physical Count ' . $this->physicalCount->count_number;
    }
}
