<?php

namespace App\Models\Enums;

enum EnergyTrackerCountingMethodEnum
{
    use UseEnumOptionsTrait;

    case ASCENDING;
    case DESCENDING;
    case FLUCTUATING;

    public static function default(): string
    {
        return self::ASCENDING->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::ASCENDING => 'Aufsteigend',
            self::DESCENDING => 'Absteigend',
            self::FLUCTUATING => 'Schwankend',
        };
    }
}
