<?php

namespace App\Enums;

enum EquipmentCondition: string
{
    case Good = 'Good';
    case Fair = 'Fair';
    case Poor = 'Poor';
    case Unserviceable = 'Unserviceable';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::Good => 'success',
            self::Fair => 'warning',
            self::Poor => 'danger',
            self::Unserviceable => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
