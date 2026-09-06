<?php

namespace App\Services\Budget;

use App\Models\Enums\BudgetStatusEnum;
use App\Models\Financial\Budget;
use Carbon\CarbonImmutable;

/**
 * Ergebnis einer Budget-Auswertung für genau einen Zeitraum.
 */
final class BudgetCalculation
{
    public function __construct(
        public readonly Budget           $budget,
        public readonly CarbonImmutable  $periodStart,
        public readonly CarbonImmutable  $periodEnd,
        public readonly float            $limit,
        public readonly float            $spent,
        public readonly float            $remaining,
        public readonly float            $percentage,
        public readonly BudgetStatusEnum $status,
        public readonly int              $daysTotal,
        public readonly int              $daysElapsed,
        public readonly int              $daysRemaining,
        public readonly float            $dailyAverage,
        public readonly float            $dailyAllowance,
        public readonly float            $projected,
        public readonly ?CarbonImmutable $projectedExceededAt,
    )
    {
    }

    public function periodLabel(): string
    {
        return $this->budget->period->formatRange($this->periodStart);
    }

    /**
     * Auf 100 gedeckelter Fortschritt – für Balkenbreiten im Template.
     */
    public function progressPercentage(): float
    {
        return min(100.0, max(0.0, $this->percentage));
    }

    /**
     * Hochrechnung überschreitet das Limit, obwohl es aktuell noch eingehalten wird.
     */
    public function isProjectedToExceed(): bool
    {
        return $this->status !== BudgetStatusEnum::EXCEEDED && $this->projected > $this->limit;
    }

    public function isOverspent(): bool
    {
        return $this->remaining < 0.0;
    }
}
