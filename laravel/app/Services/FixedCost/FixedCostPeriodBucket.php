<?php

namespace App\Services\FixedCost;

use Carbon\CarbonImmutable;

/**
 * Ein Monatsbucket im Bilanz-Verlauf (Trend).
 */
final class FixedCostPeriodBucket
{
    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly string          $label,
        public readonly float           $income,
        public readonly float           $fixedCostExpenses,
        public readonly float           $budgetExpenses,
    ) {
    }

    public function expenses(bool $withBudgets): float
    {
        return $this->fixedCostExpenses + ($withBudgets ? $this->budgetExpenses : 0.0);
    }

    public function balance(bool $withBudgets): float
    {
        return $this->income - $this->expenses($withBudgets);
    }
}
