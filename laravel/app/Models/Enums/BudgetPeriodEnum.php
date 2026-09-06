<?php

namespace App\Models\Enums;

use Carbon\CarbonImmutable;

enum BudgetPeriodEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case MONTHLY;
    case QUARTERLY;
    case YEARLY;

    public static function default(): string
    {
        return self::MONTHLY->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monatlich',
            self::QUARTERLY => 'Quartalsweise',
            self::YEARLY => 'Jährlich',
        };
    }

    public function periodEnd(CarbonImmutable $reference): CarbonImmutable
    {
        return (match ($this) {
            self::MONTHLY => $reference->endOfMonth(),
            self::QUARTERLY => $reference->endOfQuarter(),
            self::YEARLY => $reference->endOfYear(),
        })->endOfDay();
    }

    /**
     * Startdatum der Periode, die $periods Perioden vor der Periode von $reference liegt.
     */
    public function shiftStart(CarbonImmutable $reference, int $periods): CarbonImmutable
    {
        $start = $this->periodStart($reference);

        return match ($this) {
            self::MONTHLY => $start->addMonths($periods),
            self::QUARTERLY => $start->addMonths($periods * 3),
            self::YEARLY => $start->addYears($periods),
        };
    }

    public function periodStart(CarbonImmutable $reference): CarbonImmutable
    {
        return (match ($this) {
            self::MONTHLY => $reference->startOfMonth(),
            self::QUARTERLY => $reference->startOfQuarter(),
            self::YEARLY => $reference->startOfYear(),
        })->startOfDay();
    }

    /**
     * Kurzbezeichnung der Periode, z. B. "September 2026", "Q3 2026", "2026".
     */
    public function formatRange(CarbonImmutable $start): string
    {
        return match ($this) {
            self::MONTHLY => $start->translatedFormat('F Y'),
            self::QUARTERLY => sprintf('Q%d %d', $start->quarter, $start->year),
            self::YEARLY => (string)$start->year,
        };
    }
}
