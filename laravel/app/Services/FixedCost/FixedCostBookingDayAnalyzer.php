<?php

namespace App\Services\FixedCost;

use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostIntervalUnitEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Ermittelt aus den tatsächlich zugeordneten Transaktionen einer Fixkost den statistisch
 * wahrscheinlichsten Buchungstag im Monat (Median) und vergleicht ihn mit dem aktuell
 * hinterlegten `next_booking_date`.
 */
class FixedCostBookingDayAnalyzer
{
    /**
     * @param Collection<int, Transaction> $transactions Bereits auf den Auswertungszeitraum gefilterte Transaktionen.
     */
    public function analyze(
        FixedCost       $fixedCost,
        Collection      $transactions,
        int             $minOccurrences,
        CarbonImmutable $analyzedFrom,
        CarbonImmutable $analyzedTo,
    ): ?BookingDayAnalysis
    {
        if (!$this->isMonthlyCycle($fixedCost)) {
            return null;
        }

        if ($transactions->count() < $minOccurrences) {
            return null;
        }

        $currentDate = $fixedCost->{FixedCost::next_booking_date}?->toImmutable();
        if ($currentDate === null) {
            return null;
        }

        $days = $transactions
            ->map(fn(Transaction $transaction): int => Carbon::parse($transaction->{Transaction::date})->day)
            ->sort()
            ->values();

        $suggestedDay = $this->medianDay($days);
        $currentDay = $currentDate->day;
        $daysInMonth = $currentDate->daysInMonth;

        return new BookingDayAnalysis(
            suggestedDay: $suggestedDay,
            currentDay: $currentDay,
            deviationDays: $this->cyclicDeviation($suggestedDay, $currentDay, $daysInMonth),
            sampleCount: $transactions->count(),
            analyzedFrom: $analyzedFrom,
            analyzedTo: $analyzedTo,
        );
    }

    /**
     * Nur bei monatsbasierten Intervallen ist ein "Tag im Monat" überhaupt aussagekräftig.
     */
    private function isMonthlyCycle(FixedCost $fixedCost): bool
    {
        $interval = FixedCostIntervalEnum::tryFrom((string)$fixedCost->{FixedCost::interval});

        if ($interval === null) {
            return false;
        }

        if ($interval !== FixedCostIntervalEnum::CUSTOM) {
            return in_array($interval, [
                FixedCostIntervalEnum::MONTHLY,
                FixedCostIntervalEnum::TWO_MONTHS,
                FixedCostIntervalEnum::QUARTERLY,
                FixedCostIntervalEnum::HALF_YEARLY,
                FixedCostIntervalEnum::YEARLY,
            ], true);
        }

        $unit = FixedCostIntervalUnitEnum::tryFrom((string)$fixedCost->{FixedCost::custom_interval_unit});

        return in_array($unit, [FixedCostIntervalUnitEnum::MONTH, FixedCostIntervalUnitEnum::YEAR], true);
    }

    /**
     * Unterer Median: bei gerader Anzahl der kleinere der beiden mittleren Werte, damit
     * immer ein tatsächlich beobachteter Tag als Vorschlag zurückkommt.
     *
     * @param Collection<int, int> $sortedDays
     */
    private function medianDay(Collection $sortedDays): int
    {
        $count = $sortedDays->count();
        $middleIndex = intdiv($count - 1, 2);

        return $sortedDays->values()->get($middleIndex);
    }

    /**
     * Abweichung zweier Monatstage unter Berücksichtigung des Monatswechsels, z.B. gilt
     * der 31. gegenüber dem 1. als 1 Tag Abweichung statt 30.
     */
    private function cyclicDeviation(int $dayA, int $dayB, int $daysInMonth): int
    {
        $diff = abs($dayA - $dayB);

        return min($diff, $daysInMonth - $diff);
    }
}
