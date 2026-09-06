<?php

namespace App\Services\Goal;

use App\Models\Financial\Goal;
use Carbon\CarbonImmutable;

/**
 * Ergebnis einer Ziel-Auswertung zu einem Referenzzeitpunkt. Anders als beim Budget
 * gibt es keine Periode — Ziele sind kumulativ ab start_date.
 */
final class GoalCalculation
{
    public function __construct(
        public readonly Goal             $goal,
        public readonly CarbonImmutable  $startDate,
        public readonly ?CarbonImmutable $targetDate,
        public readonly CarbonImmutable  $reference,
        public readonly float            $startAmount,
        public readonly float            $targetAmount,
        public readonly float            $span,
        public readonly float            $transactionContributed,
        public readonly float            $manualContributed,
        public readonly float            $contributed,
        public readonly float            $currentAmount,
        public readonly float            $remaining,
        public readonly float            $percentage,
        public readonly int              $monthsElapsed,
        public readonly float            $monthlyAverage,
        public readonly ?float           $typicalInstallment,
        public readonly ?int             $remainingMonths,
        public readonly ?int             $remainingInstallments,
        public readonly ?CarbonImmutable $projectedCompletionAt,
        public readonly ?float           $requiredMonthlyRate,
    )
    {
    }

    /**
     * Auf 100 gedeckelter Fortschritt – für Balkenbreiten im Template.
     */
    public function progressPercentage(): float
    {
        return min(100.0, max(0.0, $this->percentage));
    }

    public function isOverachieved(): bool
    {
        return $this->percentage > 100.0;
    }

    public function currentLabel(): string
    {
        return $this->goal->type->currentLabel();
    }

    public function remainingLabel(): string
    {
        return $this->goal->type->remainingLabel();
    }

    public function statusColor(): string
    {
        if ($this->isCompleted()) {
            return 'success';
        }

        if ($this->isBehindSchedule()) {
            return 'warning';
        }

        return 'primary';
    }

    public function isCompleted(): bool
    {
        return $this->percentage >= 100.0;
    }

    public function isBehindSchedule(): bool
    {
        return $this->targetDate !== null && !$this->isCompleted() && !$this->willReachTargetDate();
    }

    /**
     * Ob die Prognose das gesetzte Zieldatum einhält. False, wenn kein Zieldatum
     * gesetzt ist oder sich mangels Beiträgen kein Abschlussdatum prognostizieren lässt.
     */
    public function willReachTargetDate(): bool
    {
        return $this->targetDate !== null
            && $this->projectedCompletionAt !== null
            && $this->projectedCompletionAt->lessThanOrEqualTo($this->targetDate);
    }
}
