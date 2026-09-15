<?php

namespace App\Services\Analysis\Calculators;

/**
 * Modul "Einnahmen": Pie-Chart nach Kategorie + Gesamtsumme.
 */
final class IncomeAnalysisCalculator extends AbstractCategoryFlowCalculator
{
    protected function isIncome(): bool
    {
        return true;
    }

    protected function totalLabel(): string
    {
        return 'Summe Einnahmen';
    }

    protected function othersLabel(): string
    {
        return 'Sonstige';
    }

    protected function uncategorizedLabel(): string
    {
        return 'Ohne Kategorie';
    }
}
