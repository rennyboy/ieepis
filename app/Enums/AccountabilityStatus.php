<?php

namespace App\Enums;

enum AccountabilityStatus: string
{
    case Normal = 'Normal';
    case Assigned = 'assigned';
    case Unassigned = 'unassigned';
    case Transferred = 'Transferred';
    case Stolen = 'Stolen';
    case Lost = 'Lost';
    case Damaged = 'Damaged';
    case ForDisposal = 'For Disposal';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Assigned => 'Assigned',
            self::Unassigned => 'Unassigned',
            self::Transferred => 'Transferred',
            self::Stolen => 'Stolen',
            self::Lost => 'Lost',
            self::Damaged => 'Damaged due to calamity',
            self::ForDisposal => 'For Disposal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Normal, self::Assigned => 'success',
            self::Unassigned, self::Transferred => 'warning',
            self::Stolen, self::Lost, self::Damaged => 'danger',
            self::ForDisposal => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
