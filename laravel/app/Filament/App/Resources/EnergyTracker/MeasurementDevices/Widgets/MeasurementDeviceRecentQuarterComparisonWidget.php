<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Widgets;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Services\EnergyTracker\MeasurementDeviceStatisticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class MeasurementDeviceRecentQuarterComparisonWidget extends ChartWidget
{
    public int|string|array $columnSpan = 'full';
    #[Locked]
    public Model|MeasurementDevice|null $record = null;
    protected ?string $heading = 'Verbrauch der letzten 3 Monate nach Jahren';
    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        if (!$this->record instanceof MeasurementDevice) {
            return ['labels' => [], 'datasets' => []];
        }

        $comparison = app(MeasurementDeviceStatisticsService::class)
            ->getYearlyRecentQuarterComparison($this->record);

        $palette = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4'];

        return [
            'labels' => $comparison['labels'],
            'datasets' => array_map(
                fn(array $dataset, int $index): array => [
                    'label' => $dataset['label'],
                    'data' => $dataset['data'],
                    'backgroundColor' => $palette[$index % count($palette)],
                    'borderRadius' => 6,
                ],
                $comparison['datasets'],
                array_keys($comparison['datasets']),
            ),
        ];
    }
}
