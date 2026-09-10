<?php

namespace App\Services\FixedCost;

use Carbon\CarbonImmutable;

/**
 * Wie viel Geld ab `asOf` noch auf dem Konto liegen muss, um alle innerhalb der Periode noch
 * ausstehenden Fixkosten sicher zu begleichen. Bereits vor `asOf` liegende Buchungen der
 * Periode fließen bewusst nicht mehr ein (siehe FixedCostBalanceService::liquidityRequirement()).
 */
final class FixedCostLiquidityRequirement
{
    /**
     * @param list<ProjectedBooking> $bookings Ausstehende Buchungen zwischen asOf und periodEnd.
     */
    public function __construct(
        public readonly CarbonImmutable  $periodStart,
        public readonly CarbonImmutable  $periodEnd,
        public readonly CarbonImmutable  $asOf,
        public readonly float            $requiredAmount,
        public readonly float            $expectedIncome,
        public readonly float            $budgetShare,
        public readonly float            $bankBalance,
        public readonly ?CarbonImmutable $bankBalanceAsOf,
        public readonly array            $bookings,
        public readonly bool             $budgetsIncluded,
        public readonly string           $currency,
    )
    {
    }

    public function shortfall(): float
    {
        return max(0.0, $this->totalRequired() - $this->bankBalance);
    }

    public function totalRequired(): float
    {
        return $this->requiredAmount + $this->budgetShare;
    }

    public function isCovered(): bool
    {
        return $this->bankBalance >= $this->totalRequired();
    }
}
