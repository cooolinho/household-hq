<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Widgets;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Services\EnergyTracker\MeasurementDeviceStatisticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class MeasurementDeviceConsumptionForecastWidget extends ChartWidget
{
    public int|string|array $columnSpan = 'full';
    #[Locked]
    public Model|MeasurementDevice|null $record = null;
    protected ?string $heading = 'Monatlicher Verlauf und Prognose';
    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        if (!$this->record instanceof MeasurementDevice) {
            return ['labels' => [], 'datasets' => []];
        }

        $trend = app(MeasurementDeviceStatisticsService::class)
            ->getMonthlyConsumptionTrend($this->record);

        return [
            'labels' => $trend['labels'],
            'datasets' => [
                [
                    'label' => 'Ist-Verbrauch',
                    'data' => $trend['actual'],
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.12)',
                    'tension' => 0.3,
                    'fill' => true,
                    'pointRadius' => 4,
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Prognose',
                    'data' => $trend['forecast'],
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.08)',
                    'tension' => 0.3,
                    'fill' => false,
                    'pointRadius' => 5,
                    'borderDash' => [8, 6],
                    'borderWidth' => 2,
                ],
            ],
        ];
    }
}
