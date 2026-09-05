<?php

namespace App\Models\Enums;

enum FixedCostIntervalUnitEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case DAY;
    case WEEK;
    case MONTH;
    case YEAR;

    public function label(): string
    {
        return match ($this) {
            self::DAY => 'Tag',
            self::WEEK => 'Woche',
            self::MONTH => 'Monat',
            self::YEAR => 'Jahr',
        };
    }
}
