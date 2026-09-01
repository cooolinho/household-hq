<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Pages;

use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\MeasurementDeviceResource;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Widgets\MeasurementDeviceConsumptionForecastWidget;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Widgets\MeasurementDeviceReadingIntervalWidget;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Widgets\MeasurementDeviceRecentQuarterComparisonWidget;
use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Widgets\MeasurementDeviceStatsOverviewWidget;
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
            MeasurementDeviceRecentQuarterComparisonWidget::class,
            MeasurementDeviceConsumptionForecastWidget::class,
            MeasurementDeviceReadingIntervalWidget::class,
        ];
    }
}
