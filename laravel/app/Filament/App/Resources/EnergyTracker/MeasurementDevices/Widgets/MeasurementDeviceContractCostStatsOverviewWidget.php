<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Widgets;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Services\EnergyTracker\MeasurementDeviceContractCostStatisticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;

class MeasurementDeviceContractCostStatsOverviewWidget extends StatsOverviewWidget
{
    public int|string|array $columnSpan = 'full';

    #[Locked]
    public Model|MeasurementDevice|null $record = null;

    protected ?string $heading = 'Vertragskosten';

    /**
     * @return Stat[]
     */
    protected function getStats(): array
    {
        if (!$this->record instanceof MeasurementDevice) {
            return [];
        }

        $stats = app(MeasurementDeviceContractCostStatisticsService::class)->getOverview($this->record);
        $currency = $stats['currency'];
        $contractName = $stats['contract_name'] ?? 'Kein aktiver Vertrag';

        return [
            Stat::make(
                'Aktueller Preis pro Einheit',
                $this->formatMoney($stats['current_unit_price'], $currency, 6),
            )
                ->description($stats['current_unit_price_valid_from']
                    ? 'Gültig ab ' . $this->formatDate($stats['current_unit_price_valid_from'])
                    : $contractName)
                ->color('primary'),
            Stat::make(
                'Prognose pro Monat',
                $this->formatMoney($stats['projected_monthly_cost'], $currency),
            )
                ->description($this->costDescription(
                    $stats['monthly_base_cost'],
                    $stats['monthly_variable_cost'],
                    $currency,
                ))
                ->color('info'),
            Stat::make(
                'Prognose pro Jahr',
                $this->formatMoney($stats['projected_annual_cost'], $currency),
            )
                ->description($this->costDescription(
                    $stats['annual_base_cost'],
                    $stats['annual_variable_cost'],
                    $currency,
                ))
                ->color('warning'),
            Stat::make(
                'Grundpreis',
                $this->formatMoney($stats['base_price'], $currency, 4),
            )
                ->description($stats['base_price_interval'] === 'YEARLY' ? 'Jährlich' : 'Monatlich')
                ->color('success'),
        ];
    }

    private function formatMoney(?float $value, string $currency, int $decimals = 2): string
    {
        if ($value === null) {
            return '-';
        }

        return number_format($value, $decimals, ',', '.') . ' ' . $currency;
    }

    private function formatDate(string $date): string
    {
        return Carbon::parse($date)->format('d.m.Y');
    }

    private function costDescription(float $baseCost, ?float $variableCost, string $currency): string
    {
        $base = 'Grundpreis ' . $this->formatMoney($baseCost, $currency);
        $variable = $variableCost === null
            ? 'Verbrauchsdaten fehlen'
            : 'Verbrauch ' . $this->formatMoney($variableCost, $currency);

        return $base . ' · ' . $variable;
    }
}
