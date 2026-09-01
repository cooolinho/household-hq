<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Widgets;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Services\EnergyTracker\MeasurementDeviceContractCostStatisticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class MeasurementDeviceContractCostComparisonWidget extends ChartWidget
{
    public int|string|array $columnSpan = 'full';

    #[Locked]
    public Model|MeasurementDevice|null $record = null;

    protected ?string $heading = 'Vertragsvergleich: prognostizierte Kosten pro Monat';

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

        $comparison = app(MeasurementDeviceContractCostStatisticsService::class)
            ->getContractComparison($this->record);
        $backgroundColors = array_map(
            fn(array $contract): string => $contract['is_active'] ? '#f59e0b' : '#94a3b8',
            $comparison['contracts'],
        );

        return [
            'labels' => $comparison['labels'],
            'datasets' => [
                [
                    'label' => 'Monatliche Kosten',
                    'data' => array_column($comparison['contracts'], 'monthly_cost'),
                    'backgroundColor' => $backgroundColors,
                    'borderRadius' => 6,
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
                ],
            ],
        ];
    }
}
