<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Widgets;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Services\EnergyTracker\MeasurementDeviceStatisticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class MeasurementDeviceReadingIntervalWidget extends ChartWidget
{
    public int|string|array $columnSpan = 'full';
    #[Locked]
    public Model|MeasurementDevice|null $record = null;
    protected ?string $heading = 'Ableseintervalle der letzten Messungen';
    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        if (!$this->record instanceof MeasurementDevice) {
            return ['labels' => [], 'datasets' => []];
        }

        $intervals = app(MeasurementDeviceStatisticsService::class)
            ->getReadingIntervals($this->record);

        return [
            'labels' => $intervals['labels'],
            'datasets' => [
                [
                    'label' => 'Tage zwischen Ablesungen',
                    'data' => $intervals['intervals'],
                    'backgroundColor' => '#10b981',
                    'borderRadius' => 6,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Zaehlerstand',
                    'data' => $intervals['readings'],
                    'type' => 'line',
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'tension' => 0.25,
                    'pointRadius' => 3,
                    'borderWidth' => 2,
                    'yAxisID' => 'y1',
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                ],
                'y1' => [
                    'beginAtZero' => false,
                    'position' => 'right',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}
