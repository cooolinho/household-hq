<?php

namespace App\Services\Dashboard\Widgets\Templates;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplate;
use App\Services\EnergyTracker\MeasurementDeviceStatisticsService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

final class EnergyTrackerDashboardWidgetTemplates
{
    /**
     * @return array<int, CustomDashboardWidgetTemplate>
     */
    public static function templates(): array
    {
        return [
            new CustomDashboardWidgetTemplate(
                key: 'measurement-consumption-trend',
                label: 'Verbrauchstrend',
                type: CustomDashboardWidgetTypeEnum::CHART,
                configurationKeys: ['measurement_device_id', 'months'],
                configurationSchema: fn(): array => [
                    Select::make('measurement_device_id')
                        ->label('Messgerät')
                        ->options(fn(): array => MeasurementDevice::query()
                            ->where(MeasurementDevice::user_id, auth()->id())
                            ->orderBy(MeasurementDevice::name)
                            ->pluck(MeasurementDevice::name, MeasurementDevice::id)
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('months')
                        ->label('Monate')
                        ->numeric()
                        ->minValue(3)
                        ->maxValue(36)
                        ->default(12)
                        ->required(),
                ],
                defaultConfiguration: fn(): array => [
                    'measurement_device_id' => null,
                    'months' => 12,
                ],
                dataResolver: function (int $userId, array $configuration): array {
                    $device = MeasurementDevice::query()
                        ->where(MeasurementDevice::user_id, $userId)
                        ->whereKey((int)$configuration['measurement_device_id'])
                        ->first();

                    if ($device === null) {
                        return ['labels' => [], 'datasets' => []];
                    }

                    $trend = app(MeasurementDeviceStatisticsService::class)->getMonthlyConsumptionTrend(
                        $device,
                        (int)$configuration['months'],
                    );

                    return [
                        'labels' => $trend['labels'],
                        'datasets' => [
                            [
                                'label' => 'Verbrauch',
                                'data' => $trend['actual'],
                                'borderColor' => '#3b82f6',
                                'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                                'tension' => 0.3,
                            ],
                            [
                                'label' => 'Prognose',
                                'data' => $trend['forecast'],
                                'borderColor' => '#f59e0b',
                                'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                                'tension' => 0.3,
                            ],
                        ],
                    ];
                },
                chartType: 'line',
                configurationNormalizer: fn(array $configuration): array => [
                    'measurement_device_id' => (int)$configuration['measurement_device_id'],
                    'months' => max(3, min(36, (int)$configuration['months'])),
                ],
            ),
        ];
    }
}
