<?php

namespace App\Filament\App\Widgets\Dashboard;

use App\Filament\App\Widgets\Dashboard\Concerns\UsesDashboardPreferences;
use App\Services\TransactionStatisticsService;
use Filament\Widgets\ChartWidget;

class CategorySpendingWidget extends ChartWidget
{
    use UsesDashboardPreferences;

    public int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 2,
    ];

    protected ?string $heading = 'Ausgaben nach Kategorie';

    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $userId = $this->getDashboardUserId();

        if (!$userId) {
            return ['labels' => [], 'datasets' => []];
        }

        $spending = app(TransactionStatisticsService::class)
            ->getCategorySpending($userId, $this->getDashboardCurrency(), 1);

        if (empty($spending)) {
            return ['labels' => [], 'datasets' => []];
        }

        // Sort by expense descending, cap at top 8 to keep the chart readable
        uasort($spending, fn($a, $b) => $b['expense'] <=> $a['expense']);
        $top = array_slice($spending, 0, 8, true);

        $labels = array_keys($top);
        $data = array_column(array_values($top), 'expense');

        // Fixed categorical palette – validated against CVD requirements
        $colors = [
            'rgba(59, 130, 246, 0.8)',   // blue
            'rgba(16, 185, 129, 0.8)',   // emerald
            'rgba(245, 158, 11, 0.8)',   // amber
            'rgba(239, 68, 68, 0.8)',    // red
            'rgba(139, 92, 246, 0.8)',   // violet
            'rgba(236, 72, 153, 0.8)',   // pink
            'rgba(14, 165, 233, 0.8)',   // sky
            'rgba(251, 146, 60, 0.8)',   // orange
        ];

        $borderColors = array_map(
            fn($c) => str_replace('0.8', '1', $c),
            $colors,
        );

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ausgaben',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderColor' => array_slice($borderColors, 0, count($data)),
                    'borderWidth' => 2,
                ],
            ],
        ];
    }
}
