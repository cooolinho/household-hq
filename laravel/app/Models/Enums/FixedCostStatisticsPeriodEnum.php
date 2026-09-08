<?php

namespace App\Models\Enums;

use Carbon\CarbonImmutable;

/**
 * Vordefinierte Zeitraum-Presets für die Fixkosten-Statistikseite.
 */
enum FixedCostStatisticsPeriodEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case CURRENT_MONTH;
    case LAST_3_MONTHS;
    case LAST_6_MONTHS;
    case LAST_12_MONTHS;
    case CURRENT_YEAR;
    case CUSTOM;

    public static function default(): string
    {
        return self::LAST_6_MONTHS->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::CURRENT_MONTH => 'Aktueller Monat',
            self::LAST_3_MONTHS => 'Letzte 3 Monate',
            self::LAST_6_MONTHS => 'Letzte 6 Monate',
            self::LAST_12_MONTHS => 'Letzte 12 Monate',
            self::CURRENT_YEAR => 'Aktuelles Jahr',
            self::CUSTOM => 'Benutzerdefiniert',
        };
    }

    public function isCustom(): bool
    {
        return $this === self::CUSTOM;
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}|null null nur bei CUSTOM.
     */
    public function resolveRange(CarbonImmutable $reference): ?array
    {
        return match ($this) {
            self::CURRENT_MONTH => ['start' => $reference->startOfMonth(), 'end' => $reference->endOfMonth()],
            self::LAST_3_MONTHS => ['start' => $reference->startOfMonth()->subMonths(2), 'end' => $reference->endOfMonth()],
            self::LAST_6_MONTHS => ['start' => $reference->startOfMonth()->subMonths(5), 'end' => $reference->endOfMonth()],
            self::LAST_12_MONTHS => ['start' => $reference->startOfMonth()->subMonths(11), 'end' => $reference->endOfMonth()],
            self::CURRENT_YEAR => ['start' => $reference->startOfYear(), 'end' => $reference->endOfYear()],
            self::CUSTOM => null,
        };
    }
}
