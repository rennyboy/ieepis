<?php

namespace App\Enums;

enum TransactionType: string
{
    case BeginningInventory = 'Beginning Inventory';
    case Issuance = 'Issuance';
    case Transfer = 'Transfer';
    case Return = 'Return';
    case Disposal = 'Disposal';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::BeginningInventory => 'gray',
            self::Issuance => 'success',
            self::Transfer => 'warning',
            self::Return => 'info',
            self::Disposal => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
