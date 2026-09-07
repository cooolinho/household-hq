<?php

namespace App\Filament\App\Resources\Financial\Goals\Widgets;

use App\Models\Financial\Goal;
use App\Services\Goal\GoalCalculationService;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class GoalProgressChartWidget extends ChartWidget
{
    public int|string|array $columnSpan = 'full';

    #[Locked]
    public Model|Goal|null $record = null;

    protected ?string $heading = 'Fortschritt über die Zeit';

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        if (!$this->record instanceof Goal) {
            return ['labels' => [], 'datasets' => []];
        }

        $series = app(GoalCalculationService::class)->progressSeries($this->record);

        $datasets = [
            [
                'label' => 'Fortschritt (kumuliert)',
                'data' => $series['cumulative'],
                'borderColor' => '#22c55e',
                'backgroundColor' => 'rgba(34, 197, 94, 0.15)',
                'fill' => true,
                'tension' => 0.3,
            ],
            [
                'label' => 'Ziel',
                'data' => array_fill(0, count($series['labels']), $series['target']),
                'borderColor' => '#94a3b8',
                'borderDash' => [6, 4],
                'pointRadius' => 0,
                'fill' => false,
            ],
        ];

        if ($series['plan'] !== null) {
            $datasets[] = [
                'label' => 'Soll-Verlauf',
                'data' => $series['plan'],
                'borderColor' => '#f59e0b',
                'borderDash' => [2, 3],
                'pointRadius' => 0,
                'fill' => false,
            ];
        }

        return [
            'labels' => $series['labels'],
            'datasets' => $datasets,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
