<?php

namespace App\Enums;

enum DocumentType: string
{
    case PAR = 'PAR';
    case ICS = 'ICS';
    case IAR = 'IAR';
    case DR = 'DR';
    case OR = 'OR';
    case SI = 'SI';
    case WMR = 'WMR';
    case RRSP = 'RRSP';
    case RRPE = 'RRPE';
    case Other = 'Other';

    public function label(): string
    {
        return match ($this) {
            self::PAR => 'PAR – Property Acknowledgment Receipt',
            self::ICS => 'ICS – Inventory Custodian Slip',
            self::IAR => 'IAR – Inspection and Acceptance Report',
            self::DR => 'DR – Delivery Receipt',
            self::OR => 'OR – Official Receipt',
            self::SI => 'SI – Sales Invoice',
            self::WMR => 'WMR – Waste Material Report',
            self::RRSP => 'RRSP – Report on the Remedies of Seized Properties',
            self::RRPE => 'RRPE – Report on Physical Count of Property & Equipment',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<string, string> Map of value → label, for use in Filament Select options().
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
