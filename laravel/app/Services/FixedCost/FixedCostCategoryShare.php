<?php

namespace App\Services\FixedCost;

/**
 * Anteil einer Fixkostenkategorie (oder der aggregierten Budgets) an den Ausgaben eines Zeitraums.
 */
final class FixedCostCategoryShare
{
    public function __construct(
        public readonly ?int   $categoryId,
        public readonly string $label,
        public readonly float  $amount,
        public readonly bool   $isBudgetAggregate = false,
    ) {
    }
}
