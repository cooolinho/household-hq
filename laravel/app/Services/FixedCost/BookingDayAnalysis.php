<?php

namespace App\Services\FixedCost;

use Carbon\CarbonImmutable;

/**
 * Ergebnis der Auswertung tatsächlicher Buchungstage einer Fixkost.
 */
final class BookingDayAnalysis
{
    public function __construct(
        public readonly int             $suggestedDay,
        public readonly int             $currentDay,
        public readonly int             $deviationDays,
        public readonly int             $sampleCount,
        public readonly CarbonImmutable $analyzedFrom,
        public readonly CarbonImmutable $analyzedTo,
    )
    {
    }

    public function hasDeviation(int $minDeviationDays): bool
    {
        return $this->deviationDays >= $minDeviationDays;
    }
}
