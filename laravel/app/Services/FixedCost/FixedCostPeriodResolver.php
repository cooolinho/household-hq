<?php

namespace App\Services\FixedCost;

use Carbon\CarbonImmutable;

/**
 * Löst die "Liquiditätsperiode" eines Benutzers auf: den Zeitraum, bis zu dessen Ende die
 * restlichen Fixkosten gedeckt sein müssen. Ein Periodenstart-Tag von 1 entspricht dem
 * Kalendermonat; jeder andere Wert verschiebt die Periodengrenzen (z.B. Gehaltseingang am 25.
 * -> Periode läuft vom 25. bis zum 24. des Folgemonats).
 *
 * Bewusst getrennt von BudgetPeriodEnum::periodStart/periodEnd und
 * FixedCostStatisticsPeriodEnum::resolveRange(), die fest kalendermonatsbasiert bleiben.
 */
class FixedCostPeriodResolver
{
    private const int MIN_DAY = 1;
    private const int MAX_DAY = 31;

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    public function resolve(int $periodStartDay, CarbonImmutable $today): array
    {
        $day = max(self::MIN_DAY, min(self::MAX_DAY, $periodStartDay));

        if ($day === 1) {
            return [
                'start' => $today->startOfMonth()->startOfDay(),
                'end' => $today->endOfMonth()->endOfDay(),
            ];
        }

        $start = $today->setDay(min($day, $today->daysInMonth))->startOfDay();

        if ($start->greaterThan($today)) {
            $start = $start->subMonthNoOverflow();
            $start = $start->setDay(min($day, $start->daysInMonth))->startOfDay();
        }

        $end = $start->addMonthNoOverflow();
        $end = $end->setDay(min($day, $end->daysInMonth))->subDay()->endOfDay();

        return ['start' => $start, 'end' => $end];
    }
}
