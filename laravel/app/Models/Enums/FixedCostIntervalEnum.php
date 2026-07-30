<?php

namespace App\Models\Enums;

enum FixedCostIntervalEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case WEEKLY;
    case TWO_WEEKS;
    case MONTHLY;
    case TWO_MONTHS;
    case QUARTERLY;
    case HALF_YEARLY;
    case YEARLY;

    public static function default(): string
    {
        return self::MONTHLY->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::WEEKLY => 'Wöchentlich',
            self::TWO_WEEKS => 'Alle zwei Wochen',
            self::MONTHLY => 'Monatlich',
            self::TWO_MONTHS => 'Alle zwei Monate',
            self::QUARTERLY => 'Vierteljährlich',
            self::HALF_YEARLY => 'Halbjährlich',
            self::YEARLY => 'Jährlich',
        };
    }
}
