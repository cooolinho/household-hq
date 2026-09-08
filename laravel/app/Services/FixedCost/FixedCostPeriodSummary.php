<?php

namespace App\Services\FixedCost;

use Carbon\CarbonImmutable;

/**
 * Bilanz auf Basis tatsächlich projizierter Buchungen für einen konkreten Zeitraum.
 */
final class FixedCostPeriodSummary
{
    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly int             $months,
        public readonly float           $income,
        public readonly float           $fixedCostExpenses,
        public readonly float           $budgetExpenses,
        public readonly bool            $budgetsIncluded,
        public readonly string          $currency,
    ) {
    }

    public function expenses(): float
    {
        return $this->fixedCostExpenses + ($this->budgetsIncluded ? $this->budgetExpenses : 0.0);
    }

    public function balance(): float
    {
        return $this->income - $this->expenses();
    }
}
