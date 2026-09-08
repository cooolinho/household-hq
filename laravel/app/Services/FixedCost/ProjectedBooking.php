<?php

namespace App\Services\FixedCost;

use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostCategory;
use Carbon\CarbonImmutable;

/**
 * Eine konkrete, auf den tatsächlichen Buchungstermin projizierte Fixkosten-Buchung.
 */
final class ProjectedBooking
{
    public function __construct(
        public readonly FixedCost       $fixedCost,
        public readonly CarbonImmutable $date,
        public readonly float           $amount,
    ) {
    }

    public function isIncome(): bool
    {
        return $this->amount > 0;
    }

    public function categoryLabel(): string
    {
        return $this->fixedCost->category?->name ?? FixedCostCategory::GROUP_NOT_CATEGORIZED;
    }
}
