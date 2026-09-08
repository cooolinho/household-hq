<?php

namespace App\Filament\App\Widgets\FixedCostStatistics;

use App\Filament\App\Widgets\FixedCostStatistics\Concerns\InteractsWithFixedCostStatisticsFilters;
use App\Services\FixedCost\FixedCostCategoryShare;
use Filament\Widgets\ChartWidget;

class FixedCostStatisticsCategoryChartWidget extends ChartWidget
{
    use InteractsWithFixedCostStatisticsFilters;

    public int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 2,
    ];

    protected ?string $heading = 'Verteilung nach Kategorie';

    protected ?string $maxHeight = '320px';

    private const array PALETTE = [
        'rgba(59, 130, 246, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(239, 68, 68, 0.8)',
        'rgba(139, 92, 246, 0.8)',
        'rgba(236, 72, 153, 0.8)',
        'rgba(14, 165, 233, 0.8)',
        'rgba(251, 146, 60, 0.8)',
        'rgba(20, 184, 166, 0.8)',
    ];

    private const string BUDGET_COLOR = '#f59e0b';

    protected function getType(): string
    {
        return 'doughnut';
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

        $shares = $this->balanceService()->categoryDistribution(
            (int)$userId,
            $start,
            $end,
            $this->includeBudgets(),
        );

        if ($shares === []) {
            return ['labels' => [], 'datasets' => []];
        }

        $labels = array_map(fn(FixedCostCategoryShare $share): string => $share->label, $shares);
        $data = array_map(fn(FixedCostCategoryShare $share): float => $share->amount, $shares);

        $colors = [];
        $paletteIndex = 0;

        foreach ($shares as $share) {
            if ($share->isBudgetAggregate) {
                $colors[] = self::BUDGET_COLOR;

                continue;
            }

            $colors[] = self::PALETTE[$paletteIndex % count(self::PALETTE)];
            $paletteIndex++;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ausgaben',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 2,
                ],
            ],
        ];
    }
}
