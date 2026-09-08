<?php

namespace App\Filament\App\Widgets\FixedCostStatistics;

use App\Filament\App\Widgets\FixedCostStatistics\Concerns\InteractsWithFixedCostStatisticsFilters;
use App\Services\FixedCost\FixedCostPeriodBucket;
use Filament\Widgets\ChartWidget;

class FixedCostStatisticsTrendChartWidget extends ChartWidget
{
    use InteractsWithFixedCostStatisticsFilters;

    public int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Verlauf im Zeitraum';

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'line';
    }

    public function getDescription(): ?string
    {
        return 'Fixkosten nach tatsächlichem Buchungstermin, Budgets monatlich normalisiert.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $userId = auth()->id();

        if (!$userId) {
            return ['labels' => [], 'datasets' => []];
        }

        ['start' => $start, 'end' => $end] = $this->resolveRange();
        $includeBudgets = $this->includeBudgets();

        $buckets = $this->balanceService()->trend((int)$userId, $start, $end);

        $labels = array_map(fn(FixedCostPeriodBucket $bucket): string => $bucket->label, $buckets);

        $datasets = [
            [
                'label' => 'Einnahmen',
                'data' => array_map(fn(FixedCostPeriodBucket $bucket): float => $bucket->income, $buckets),
                'borderColor' => '#10b981',
                'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                'tension' => 0.3,
            ],
            [
                'label' => 'Fixkosten',
                'data' => array_map(fn(FixedCostPeriodBucket $bucket): float => $bucket->fixedCostExpenses, $buckets),
                'borderColor' => '#ef4444',
                'backgroundColor' => 'rgba(239, 68, 68, 0.15)',
                'tension' => 0.3,
            ],
        ];

        if ($includeBudgets) {
            $datasets[] = [
                'label' => 'Budgets',
                'data' => array_map(fn(FixedCostPeriodBucket $bucket): float => $bucket->budgetExpenses, $buckets),
                'borderColor' => '#f59e0b',
                'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                'tension' => 0.3,
            ];
        }

        $datasets[] = [
            'label' => 'Bilanz',
            'data' => array_map(fn(FixedCostPeriodBucket $bucket): float => $bucket->balance($includeBudgets), $buckets),
            'borderColor' => '#3b82f6',
            'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
            'tension' => 0.3,
        ];

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }
}
