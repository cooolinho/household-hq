<?php

namespace App\Services;

use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Financial\FixedCost;
use Carbon\CarbonImmutable;

class FixedCostNextBookingDateCalculator
{
    public function resolveNextBookingDate(FixedCost $fixedCost, CarbonImmutable $today): ?CarbonImmutable
    {
        $referenceDate = $fixedCost->next_booking_date?->toImmutable()->startOfDay()
            ?? $fixedCost->created_at?->toImmutable()->startOfDay()
            ?? $today;

        $candidate = $referenceDate;
        if ($candidate->greaterThan($today)) {
            return $this->isAllowedByEndMode($fixedCost, $candidate) ? $candidate : null;
        }

        // Catch up the next booking date until it is strictly in the future.
        while ($candidate->lessThanOrEqualTo($today)) {
            $interval = $this->resolveIntervalForDate($fixedCost, $candidate);
            $candidate = $this->addInterval($candidate, $interval);

            if (!$this->isAllowedByEndMode($fixedCost, $candidate)) {
                return null;
            }
        }

        return $candidate;
    }

    private function resolveIntervalForDate(FixedCost $fixedCost, CarbonImmutable $date): string
    {
        if (
            $fixedCost->ends_mode === FixedCostEndsModeEnum::EXTENDED->name
            && $fixedCost->extended_date !== null
            && $fixedCost->extended_interval !== null
            && $date->greaterThanOrEqualTo($fixedCost->extended_date->toImmutable()->startOfDay())
        ) {
            return $fixedCost->extended_interval;
        }

        return $fixedCost->interval;
    }

    private function isAllowedByEndMode(FixedCost $fixedCost, CarbonImmutable $candidate): bool
    {
        if ($fixedCost->ends_mode !== FixedCostEndsModeEnum::ENDS->name) {
            return true;
        }

        if ($fixedCost->ends_date === null) {
            return true;
        }

        return $candidate->lessThanOrEqualTo($fixedCost->ends_date->toImmutable()->startOfDay());
    }

    private function addInterval(CarbonImmutable $date, string $interval): CarbonImmutable
    {
        return match ($interval) {
            'WEEKLY' => $date->addWeek(),
            'TWO_WEEKS' => $date->addWeeks(2),
            'MONTHLY' => $date->addMonth(),
            'TWO_MONTHS' => $date->addMonths(2),
            'QUARTERLY' => $date->addMonths(3),
            'HALF_YEARLY' => $date->addMonths(6),
            'YEARLY' => $date->addYear(),
            default => $date->addMonth(),
        };
    }
}

