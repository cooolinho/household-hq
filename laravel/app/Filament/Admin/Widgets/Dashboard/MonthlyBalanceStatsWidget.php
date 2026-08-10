<?php

namespace App\Filament\Admin\Widgets\Dashboard;

use App\Filament\Admin\Widgets\Dashboard\Concerns\UsesDashboardPreferences;
use App\Models\DashboardWidgetPreference;
use App\Services\DashboardMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MonthlyBalanceStatsWidget extends StatsOverviewWidget
{
    use UsesDashboardPreferences;

    public int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Monatliche Kostenbilanz';

    /**
     * @return Stat[]
     */
    protected function getStats(): array
    {
        $userId = $this->getDashboardUserId();

        if (!$userId) {
            return [];
        }

        $metrics = app(DashboardMetricsService::class)
            ->getMonthlyBalanceData($userId, $this->getDashboardCurrency());

        $currency = $metrics['currency'];
        $forecast = $metrics['forecast'];
        $actual = $metrics['actual'];
        $balanceMode = $this->getDashboardBalanceMode();

        $stats = [];

        if (in_array($balanceMode, [DashboardWidgetPreference::BALANCE_MODE_BOTH, DashboardWidgetPreference::BALANCE_MODE_FORECAST], true)) {
            $stats[] = Stat::make('Prognose Bilanz', $this->formatMoney($forecast['balance'], $currency, true))
                ->description('Monat auf Basis aktiver Fixkosten')
                ->color($forecast['balance'] < 0 ? 'danger' : 'success');

            $stats[] = Stat::make('Prognose Ausgaben', $this->formatMoney($forecast['expenses'], $currency))
                ->description('Gewichtete Monatsausgaben aus Fixkosten')
                ->color('danger');
        }

        if (in_array($balanceMode, [DashboardWidgetPreference::BALANCE_MODE_BOTH, DashboardWidgetPreference::BALANCE_MODE_ACTUAL], true)) {
            $stats[] = Stat::make('Ist Bilanz', $this->formatMoney($actual['balance'], $currency, true))
                ->description('Monat aus gebuchten Transaktionen')
                ->color($actual['balance'] < 0 ? 'danger' : 'success');

            $stats[] = Stat::make('Ist Ausgaben', $this->formatMoney($actual['expenses'], $currency))
                ->description('Monatliche Ausgaben nach Buchungsdatum')
                ->color('danger');
        }

        return $stats;
    }

    private function formatMoney(float $value, string $currency, bool $signed = false): string
    {
        $prefix = $signed && $value > 0 ? '+' : '';

        return $prefix . number_format($value, 2, ',', '.') . ' ' . $currency;
    }
}

