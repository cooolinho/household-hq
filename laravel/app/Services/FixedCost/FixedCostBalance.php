<?php

namespace App\Services\FixedCost;

use App\Models\Financial\FixedCost;

/**
 * Auf Monats- bzw. Jahresbasis normalisierte Bilanz aus Fixkosten und optional Budgets.
 */
final class FixedCostBalance
{
    /**
     * @param list<FixedCost> $invalidFixedCosts
     */
    public function __construct(
        public readonly float  $monthlyIncome,
        public readonly float  $monthlyFixedCostExpenses,
        public readonly float  $monthlyBudgetExpenses,
        public readonly float  $yearlyIncome,
        public readonly float  $yearlyFixedCostExpenses,
        public readonly float  $yearlyBudgetExpenses,
        public readonly bool   $budgetsIncluded,
        public readonly int    $budgetCount,
        public readonly string $currency,
        public readonly array  $invalidFixedCosts = [],
    ) {
    }

    public function monthlyExpenses(): float
    {
        return $this->monthlyFixedCostExpenses + ($this->budgetsIncluded ? $this->monthlyBudgetExpenses : 0.0);
    }

    public function monthlyBalance(): float
    {
        return $this->monthlyIncome - $this->monthlyExpenses();
    }

    public function yearlyExpenses(): float
    {
        return $this->yearlyFixedCostExpenses + ($this->budgetsIncluded ? $this->yearlyBudgetExpenses : 0.0);
    }

    public function yearlyBalance(): float
    {
        return $this->yearlyIncome - $this->yearlyExpenses();
    }

    public function hasInvalidFixedCosts(): bool
    {
        return $this->invalidFixedCosts !== [];
    }

    /**
     * Klon mit gekipptem Budget-Flag, ohne erneute Datenbankabfrage.
     */
    public function withBudgets(bool $include): self
    {
        return new self(
            monthlyIncome: $this->monthlyIncome,
            monthlyFixedCostExpenses: $this->monthlyFixedCostExpenses,
            monthlyBudgetExpenses: $this->monthlyBudgetExpenses,
            yearlyIncome: $this->yearlyIncome,
            yearlyFixedCostExpenses: $this->yearlyFixedCostExpenses,
            yearlyBudgetExpenses: $this->yearlyBudgetExpenses,
            budgetsIncluded: $include,
            budgetCount: $this->budgetCount,
            currency: $this->currency,
            invalidFixedCosts: $this->invalidFixedCosts,
        );
    }
}
