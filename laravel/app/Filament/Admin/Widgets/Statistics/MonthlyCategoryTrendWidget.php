<?php

namespace App\Filament\Admin\Widgets\Statistics;

use App\Services\TransactionStatisticsService;
use Filament\Widgets\ChartWidget;

class MonthlyCategoryTrendWidget extends ChartWidget
{
    public int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Ausgaben-Trend Top-5 Kategorien (6 Monate)';

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'line';
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

        $monthly = $service->getMonthlySpendingByCategory($userId, 'EUR', 6);
        $top5 = $service->getTopCategories($userId, 'EUR', 'expense', 5);

        if (empty($top5) || empty($monthly)) {
            return ['labels' => [], 'datasets' => []];
        }

        $labels = array_keys($monthly);
        $categories = array_column($top5, 'name');

        // Color palette per series
        $palette = [
            ['border' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.12)'],
            ['border' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.12)'],
            ['border' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.12)'],
            ['border' => '#ef4444', 'bg' => 'rgba(239, 68, 68, 0.12)'],
            ['border' => '#8b5cf6', 'bg' => 'rgba(139, 92, 246, 0.12)'],
        ];

        $datasets = [];

        foreach ($categories as $i => $cat) {
            $data = [];
            foreach ($monthly as $monthData) {
                // Expenses are stored as negative net amounts; show absolute value
                $net = $monthData[$cat] ?? 0;
                $data[] = $net < 0 ? abs($net) : 0;
            }

            $color = $palette[$i % count($palette)];
            $datasets[] = [
                'label' => $cat,
                'data' => $data,
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
                'tension' => 0.3,
                'fill' => true,
                'pointRadius' => 4,
                'borderWidth' => 2,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }
}
