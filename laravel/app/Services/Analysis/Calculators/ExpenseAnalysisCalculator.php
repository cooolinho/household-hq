<?php

namespace App\Services\Analysis\Calculators;

/**
 * Modul "Ausgaben": Pie-Chart nach Kategorie + Gesamtsumme.
 */
final class ExpenseAnalysisCalculator extends AbstractCategoryFlowCalculator
{
    protected function isIncome(): bool
    {
        return false;
    }

    protected function totalLabel(): string
    {
        return 'Summe Ausgaben';
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
