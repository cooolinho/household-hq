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

    /** Wiederholungen pro Jahr für genau eine Einheit. */
    public function occurrencesPerYear(): float
    {
        return match ($this) {
            self::DAY => 365.25,
            self::WEEK => 52.0,
            self::MONTH => 12.0,
            self::YEAR => 1.0,
        };
    }
}
