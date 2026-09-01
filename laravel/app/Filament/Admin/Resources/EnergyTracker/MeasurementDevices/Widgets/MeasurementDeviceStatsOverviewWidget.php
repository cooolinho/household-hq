<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Widgets;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Services\EnergyTracker\MeasurementDeviceStatisticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class MeasurementDeviceStatsOverviewWidget extends StatsOverviewWidget
{
    public int|string|array $columnSpan = 'full';
    #[Locked]
    public Model|MeasurementDevice|null $record = null;
    protected ?string $heading = 'Messgeraet-Statistik';

    /**
     * @return Stat[]
     */
    protected function getStats(): array
    {
        if (!$this->record instanceof MeasurementDevice) {
            return [];
        }

        $stats = app(MeasurementDeviceStatisticsService::class)->getOverview($this->record);
        $unit = $this->record->{MeasurementDevice::counting_unit} ?: 'Einheiten';

        return [
            Stat::make('Aktueller Stand', $this->formatNumber($stats['latest_reading_value']) . ' ' . $unit)
                ->description($stats['latest_reading_date'] ? 'Ablesung vom ' . $this->formatDate($stats['latest_reading_date']) : 'Noch keine Ablesung')
                ->color('primary'),
            Stat::make('Verbrauch letzte 90 Tage', $this->formatNumber($stats['consumption_90d']) . ' ' . $unit)
                ->description($stats['average_daily_consumption_90d'] !== null
                    ? $this->formatNumber($stats['average_daily_consumption_90d'], 3) . ' ' . $unit . '/Tag'
                    : 'Zu wenige Datenpunkte fuer Durchschnitt')
                ->color('info'),
            Stat::make('30-Tage-Prognose', $stats['forecast_30d'] !== null ? $this->formatNumber($stats['forecast_30d']) . ' ' . $unit : '-')
                ->description($stats['forecast_30d'] !== null
                    ? 'Auf Basis der letzten 3 Monate'
                    . ($stats['forecast_next_reading_date'] ? ' · naechste Ablesung ca. ' . $this->formatDate($stats['forecast_next_reading_date']) : '')
                    : 'Keine belastbare Prognose verfuegbar')
                ->color('warning'),
            Stat::make('Ableseintervall', $stats['average_interval_days'] !== null ? $this->formatNumber($stats['average_interval_days'], 1) . ' Tage' : '-')
                ->description($stats['latest_interval_days'] !== null
                    ? 'Letzter Abstand: ' . $stats['latest_interval_days'] . ' Tage'
                    : 'Abstaende noch nicht verfuegbar')
                ->color('success'),
        ];
    }

    private function formatNumber(?float $value, int $decimals = 2): string
    {
        if ($value === null) {
            return '-';
        }

        return number_format($value, $decimals, ',', '.');
    }

    private function formatDate(string $date): string
    {
        return \Illuminate\Support\Carbon::parse($date)->format('d.m.Y');
    }
}
