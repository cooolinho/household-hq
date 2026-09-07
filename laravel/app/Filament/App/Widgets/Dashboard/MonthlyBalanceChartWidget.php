<?php

namespace App\Filament\App\Widgets\Dashboard;

use App\Filament\App\Widgets\Dashboard\Concerns\UsesDashboardPreferences;
use App\Models\DashboardWidgetPreference;
use App\Services\DashboardMetricsService;
use Filament\Widgets\ChartWidget;

class MonthlyBalanceChartWidget extends ChartWidget
{
    use UsesDashboardPreferences;

    public int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 2,
    ];

    protected ?string $heading = 'Bilanz Trend (6 Monate)';

    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $userId = $this->getDashboardUserId();

        if (!$userId) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $trend = app(DashboardMetricsService::class)
            ->getMonthlyBalanceTrend($userId, $this->getDashboardCurrency(), 6);

        $mode = $this->getDashboardBalanceMode();
        $datasets = [];

        if (in_array($mode, [DashboardWidgetPreference::BALANCE_MODE_BOTH, DashboardWidgetPreference::BALANCE_MODE_FORECAST], true)) {
            $datasets[] = [
                'label' => 'Prognose',
                'data' => $trend['forecastBalances'],
                'borderColor' => '#f59e0b',
                'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                'tension' => 0.3,
            ];
        }

        if (in_array($mode, [DashboardWidgetPreference::BALANCE_MODE_BOTH, DashboardWidgetPreference::BALANCE_MODE_ACTUAL], true)) {
            $datasets[] = [
                'label' => 'Ist',
                'data' => $trend['actualBalances'],
                'borderColor' => '#3b82f6',
                'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                'tension' => 0.3,
            ];
        }

        return [
            'labels' => $trend['labels'],
            'datasets' => $datasets,
        ];
    }
}

