<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Widgets;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Services\EnergyTracker\MeasurementDeviceContractCostStatisticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class MeasurementDeviceContractCostForecastWidget extends ChartWidget
{
    public int|string|array $columnSpan = 'full';

    #[Locked]
    public Model|MeasurementDevice|null $record = null;

    protected ?string $heading = 'Kostenprognose für die nächsten 12 Monate';

    protected ?string $maxHeight = '340px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        if (!$this->record instanceof MeasurementDevice) {
            return ['labels' => [], 'datasets' => []];
        }

        $forecast = app(MeasurementDeviceContractCostStatisticsService::class)
            ->getForecast($this->record, 12);

        return [
            'labels' => $forecast['labels'],
            'datasets' => [
                [
                    'label' => 'Grundpreis',
                    'data' => $forecast['base_costs'],
                    'backgroundColor' => '#94a3b8',
                    'borderRadius' => 5,
                    'stack' => 'costs',
                ],
                [
                    'label' => 'Verbrauchskosten',
                    'data' => $forecast['variable_costs'],
                    'backgroundColor' => '#3b82f6',
                    'borderRadius' => 5,
                    'stack' => 'costs',
                ],
                [
                    'type' => 'line',
                    'label' => 'Gesamtkosten',
                    'data' => $forecast['costs'],
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'tension' => 0.3,
                    'fill' => false,
                    'pointRadius' => 4,
                    'borderWidth' => 2,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'beginAtZero' => true,
                    'stacked' => true,
                ],
            ],
        ];
    }
}
