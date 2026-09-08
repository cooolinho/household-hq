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
    case CUSTOM;

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
            self::CUSTOM => 'Benutzerdefiniert',
        };
    }

    /**
     * Buchungen pro Jahr. null für CUSTOM, weil dafür die Werte am Datensatz nötig sind.
     */
    public function occurrencesPerYear(): ?float
    {
        return match ($this) {
            self::WEEKLY => 52.0,
            self::TWO_WEEKS => 26.0,
            self::MONTHLY => 12.0,
            self::TWO_MONTHS => 6.0,
            self::QUARTERLY => 4.0,
            self::HALF_YEARLY => 2.0,
            self::YEARLY => 1.0,
            self::CUSTOM => null,
        };
    }
}
