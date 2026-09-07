<?php

namespace App\Filament\App\Widgets\Statistics;

use App\Services\TransactionStatisticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CategoryStatsOverviewWidget extends StatsOverviewWidget
{
    public int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Übersicht';

    /**
     * @return Stat[]
     */
    protected function getStats(): array
    {
        $userId = auth()->id();

        if (!$userId) {
            return [];
        }

        $service = app(TransactionStatisticsService::class);
        $currency = 'EUR';

        $topExpenses = $service->getTopCategories($userId, $currency, 'expense', 1);
        $topIncome = $service->getTopCategories($userId, $currency, 'income', 1);
        $uncategorized = $service->getUncategorizedStats($userId, $currency);

        $stats = [];

        if (!empty($topExpenses)) {
            $stats[] = Stat::make(
                'Meiste Ausgaben',
                $topExpenses[0]['name']
            )
                ->description($this->formatMoney($topExpenses[0]['amount'], $currency) . ' · ' . $topExpenses[0]['count'] . ' Buchungen')
                ->color('danger');
        }

        if (!empty($topIncome)) {
            $stats[] = Stat::make(
                'Meiste Einnahmen',
                $topIncome[0]['name']
            )
                ->description($this->formatMoney($topIncome[0]['amount'], $currency) . ' · ' . $topIncome[0]['count'] . ' Buchungen')
                ->color('success');
        }

        $stats[] = Stat::make(
            'Unkategorisiert',
            $uncategorized['count'] . ' Buchungen'
        )
            ->description(
                'Ausgaben: ' . $this->formatMoney($uncategorized['total_expense'], $currency)
                . ' · Einnahmen: ' . $this->formatMoney($uncategorized['total_income'], $currency)
            )
            ->color($uncategorized['count'] > 0 ? 'warning' : 'success');

        return $stats;
    }

    private function formatMoney(float $value, string $currency): string
    {
        return number_format($value, 2, ',', '.') . ' ' . $currency;
    }
}
