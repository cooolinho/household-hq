<?php

namespace App\Filament\App\Widgets\Statistics;

use App\Services\TransactionStatisticsService;
use Filament\Widgets\ChartWidget;

class CategoryPieChartWidget extends ChartWidget
{
    public int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 2,
    ];

    protected ?string $heading = 'Ausgaben nach Kategorie (aktueller Monat)';

    protected ?string $maxHeight = '320px';

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

        $spending = app(TransactionStatisticsService::class)
            ->getCategorySpending($userId, 'EUR', 1);

        if (empty($spending)) {
            return ['labels' => [], 'datasets' => []];
        }

        uasort($spending, fn($a, $b) => $b['expense'] <=> $a['expense']);
        $top = array_slice($spending, 0, 9, true);

        $labels = array_keys($top);
        $data = array_column(array_values($top), 'expense');

        $colors = [
            'rgba(59, 130, 246, 0.8)',
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(139, 92, 246, 0.8)',
            'rgba(236, 72, 153, 0.8)',
            'rgba(14, 165, 233, 0.8)',
            'rgba(251, 146, 60, 0.8)',
            'rgba(20, 184, 166, 0.8)',
        ];

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ausgaben',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 2,
                ],
            ],
        ];
    }
}
