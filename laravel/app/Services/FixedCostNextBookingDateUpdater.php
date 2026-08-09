<?php

namespace App\Services;

use App\Models\Financial\FixedCost;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;

readonly class FixedCostNextBookingDateUpdater
{
    public function __construct(private FixedCostNextBookingDateCalculator $calculator)
    {
    }

    public function updateDueDates(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $updated = 0;

        FixedCost::query()
            ->orderBy(FixedCost::id)
            ->chunkById(100, function ($fixedCosts) use ($today, &$updated) {
                foreach ($fixedCosts as $fixedCost) {
                    $nextDate = $this->calculator->resolveNextBookingDate($fixedCost, $today);
                    if (!$nextDate instanceof DateTimeInterface) {
                        Log::warning('FixedCostNextBookingDateUpdater: Could not resolve next booking date for FixedCost ID ' . $fixedCost->id);
                        continue;
                    }

                    $current = $fixedCost->next_booking_date?->toImmutable()->startOfDay();
                    if ($current?->equalTo($nextDate) ?? ($current === null)) {
                        continue;
                    }

                    $fixedCost->next_booking_date = $nextDate;
                    $fixedCost->save();
                    $updated++;
                }
            });

        return $updated;
    }
}

