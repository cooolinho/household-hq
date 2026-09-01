<?php

namespace App\Filament\Admin\Widgets\Statistics;

use App\Services\TransactionStatisticsService;
use Filament\Widgets\ChartWidget;

class CategoryBarChartWidget extends ChartWidget
{
    public int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 2,
    ];

    protected ?string $heading = 'Einnahmen & Ausgaben nach Kategorie (6 Monate)';

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'bar';
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

        $service = app(TransactionStatisticsService::class);

        // Get top categories first for labels
        $top = $service->getTopCategories($userId, 'EUR', 'expense', 8);

        if (empty($top)) {
            return ['labels' => [], 'datasets' => []];
        }

        $spending = $service->getCategorySpending($userId, 'EUR', 6);

        $categories = array_column($top, 'name');

        $incomeData = [];
        $expenseData = [];

        foreach ($categories as $cat) {
            $incomeData[] = $spending[$cat]['income'] ?? 0;
            $expenseData[] = $spending[$cat]['expense'] ?? 0;
        }

        return [
            'labels' => $categories,
            'datasets' => [
                [
                    'label' => 'Einnahmen',
                    'data' => $incomeData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Ausgaben',
                    'data' => $expenseData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.7)',
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'borderWidth' => 2,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true],
            ],
            'scales' => [
                'x' => ['stacked' => false],
                'y' => ['stacked' => false],
            ],
        ];
    }
}
