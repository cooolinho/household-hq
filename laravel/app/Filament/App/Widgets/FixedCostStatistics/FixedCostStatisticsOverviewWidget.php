<?php

namespace App\Filament\App\Widgets\FixedCostStatistics;

use App\Filament\App\Widgets\FixedCostStatistics\Concerns\InteractsWithFixedCostStatisticsFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FixedCostStatisticsOverviewWidget extends StatsOverviewWidget
{
    use InteractsWithFixedCostStatisticsFilters;

    public int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Bilanz im Zeitraum';

    /**
     * @return Stat[]
     */
    protected function getStats(): array
    {
        $userId = auth()->id();

        if (!$userId) {
            return [];
        }

        ['start' => $start, 'end' => $end] = $this->resolveRange();
        $includeBudgets = $this->includeBudgets();

        $summary = $this->balanceService()->periodSummary(
            (int)$userId,
            $start,
            $end,
            $includeBudgets,
        );

        $stats = [
            Stat::make('Einnahmen', $this->formatMoney($summary->income))
                ->color('success'),
            Stat::make('Fixkosten-Ausgaben', $this->formatMoney($summary->fixedCostExpenses))
                ->color('danger'),
        ];

        if ($includeBudgets && $summary->budgetExpenses > 0) {
            $stats[] = Stat::make('Budgets', $this->formatMoney($summary->budgetExpenses))
                ->description('Monatlich normalisiert · ' . $summary->months . ' Monat(e)')
                ->color('warning');
        }

        $stats[] = Stat::make('Bilanz', $this->formatMoney($summary->balance(), true))
            ->description($start->translatedFormat('M Y') . ' – ' . $end->translatedFormat('M Y'))
            ->color($summary->balance() >= 0 ? 'success' : 'danger');

        return $stats;
    }

    private function formatMoney(float $value, bool $signed = false): string
    {
        return ($signed && $value > 0 ? '+' : '') . number_format($value, 2, ',', '.') . ' €';
    }
}
