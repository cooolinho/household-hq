<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Pages;

use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\MeasurementDeviceResource;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Widgets\MeasurementDeviceConsumptionForecastWidget;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Widgets\MeasurementDeviceContractCostComparisonWidget;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Widgets\MeasurementDeviceContractCostForecastWidget;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Widgets\MeasurementDeviceContractCostStatsOverviewWidget;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Widgets\MeasurementDeviceReadingIntervalWidget;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Widgets\MeasurementDeviceRecentQuarterComparisonWidget;
use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Widgets\MeasurementDeviceStatsOverviewWidget;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMeasurementDevice extends ViewRecord
{
    protected static string $resource = MeasurementDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            MeasurementDeviceStatsOverviewWidget::class,
            MeasurementDeviceContractCostStatsOverviewWidget::class,
            MeasurementDeviceContractCostComparisonWidget::class,
            MeasurementDeviceRecentQuarterComparisonWidget::class,
            MeasurementDeviceConsumptionForecastWidget::class,
            MeasurementDeviceContractCostForecastWidget::class,
            MeasurementDeviceReadingIntervalWidget::class,
        ];
    }
}
