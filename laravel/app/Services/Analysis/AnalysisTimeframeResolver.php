<?php

namespace App\Services\Analysis;

use App\Models\DashboardWidgetPreference;
use App\Models\Enums\AnalysisTimeframeEnum;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Berechnet Start-/Enddatum eines Auswertungs-Zeitraums.
 *
 * "Monatsmitte" berücksichtigt den benutzerspezifischen `period_start_day`
 * (Standard 1 = Kalendermonat). Bei period_start_day = 15 läuft die Periode
 * z. B. vom 15. bis zum 14. des Folgemonats.
 */
final class AnalysisTimeframeResolver
{
    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    public function resolve(int $userId, AnalysisConfiguration $config, ?CarbonImmutable $reference = null): array
    {
        $reference ??= CarbonImmutable::now();
        $timeframe = $config->timeframe;

        if ($timeframe === null) {
            throw new InvalidArgumentException('Für dieses Modul ist kein Zeitraum konfiguriert.');
        }

        return match ($timeframe) {
            AnalysisTimeframeEnum::CUSTOM => $this->custom($config, $reference),
            AnalysisTimeframeEnum::MONTHLY => $this->monthly($reference),
            AnalysisTimeframeEnum::MID_MONTH => $this->midMonth($userId, $reference),
            AnalysisTimeframeEnum::QUARTERLY => $this->quarterly($reference),
            AnalysisTimeframeEnum::YEARLY => $this->yearly($reference),
        };
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    private function custom(AnalysisConfiguration $config, CarbonImmutable $reference): array
    {
        $start = $config->dateFrom ?? $reference->startOfMonth();
        $end = $config->dateTo ?? $reference->endOfMonth();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return ['start' => $start->startOfDay(), 'end' => $end->endOfDay()];
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    private function monthly(CarbonImmutable $reference): array
    {
        return ['start' => $reference->startOfMonth(), 'end' => $reference->endOfMonth()];
    }

    /**
     * "Monatsmitte": Periode von period_start_day bis (period_start_day - 1) des Folgemonats.
     * Bei period_start_day = 1 entspricht dies dem Kalendermonat.
     *
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    private function midMonth(int $userId, CarbonImmutable $reference): array
    {
        $periodStartDay = $this->periodStartDay($userId);

        if ($periodStartDay <= 1) {
            return $this->monthly($reference);
        }

        $daysInCurrentMonth = $reference->daysInMonth;
        $anchorDay = min($periodStartDay, $daysInCurrentMonth);

        if ($reference->day >= $anchorDay) {
            $start = $reference->setDay($anchorDay)->startOfDay();
            $nextMonth = $reference->addMonthNoOverflow();
            $endDay = min($periodStartDay - 1, $nextMonth->daysInMonth);
            $end = $nextMonth->setDay(max(1, $endDay))->endOfDay();
        } else {
            $previousMonth = $reference->subMonthNoOverflow();
            $startDay = min($periodStartDay, $previousMonth->daysInMonth);
            $start = $previousMonth->setDay($startDay)->startOfDay();
            $endDay = min($periodStartDay - 1, $daysInCurrentMonth);
            $end = $reference->setDay(max(1, $endDay))->endOfDay();
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    private function quarterly(CarbonImmutable $reference): array
    {
        return ['start' => $reference->startOfQuarter(), 'end' => $reference->endOfQuarter()];
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    private function yearly(CarbonImmutable $reference): array
    {
        return ['start' => $reference->startOfYear(), 'end' => $reference->endOfYear()];
    }

    private function periodStartDay(int $userId): int
    {
        return max(1, min(31, (int) DashboardWidgetPreference::forUser($userId)->{DashboardWidgetPreference::period_start_day}));
    }
}
