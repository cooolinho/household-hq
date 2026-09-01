<?php

namespace App\Services\EnergyTracker;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\EnergyTracker\MeasurementDeviceContractPrice;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class MeasurementDeviceContractCostStatisticsService
{
    public function __construct(
        private readonly MeasurementDeviceStatisticsService $measurementStatistics,
    )
    {
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     contracts: array<int, array{
     *         name: string,
     *         is_active: bool,
     *         currency: string,
     *         monthly_cost: ?float,
     *         annual_cost: ?float
     *     }>
     * }
     */
    public function getContractComparison(MeasurementDevice $device): array
    {
        $contracts = $device->relationLoaded(MeasurementDevice::has_many_contracts)
            ? $device->contracts
            : $device->contracts()
                ->with(MeasurementDeviceContract::has_many_prices)
                ->orderBy(MeasurementDeviceContract::name)
                ->get();
        $measurementStats = $this->measurementStatistics->getOverview($device);
        $dailyConsumption = $this->estimatedDailyConsumption($measurementStats);
        $today = CarbonImmutable::today();
        $monthlyConsumption = $dailyConsumption === null
            ? null
            : round($dailyConsumption * $today->daysInMonth, 2);
        $annualConsumption = $dailyConsumption === null
            ? null
            : round($dailyConsumption * 365, 2);

        $comparison = $contracts
            ->sortBy(MeasurementDeviceContract::name)
            ->values()
            ->map(function (MeasurementDeviceContract $contract) use (
                $monthlyConsumption,
                $annualConsumption,
                $today,
            ): array {
                $contract->loadMissing(MeasurementDeviceContract::has_many_prices);
                $price = $contract->currentPrice($today);
                $unitPrice = $price ? (float)$price->{MeasurementDeviceContractPrice::unit_price} : null;
                $monthlyVariableCost = $monthlyConsumption !== null && $unitPrice !== null
                    ? round($monthlyConsumption * $unitPrice, 2)
                    : null;
                $annualVariableCost = $annualConsumption !== null && $unitPrice !== null
                    ? round($annualConsumption * $unitPrice, 2)
                    : null;

                return [
                    'name' => (string)$contract->{MeasurementDeviceContract::name},
                    'is_active' => (bool)$contract->{MeasurementDeviceContract::is_active},
                    'currency' => strtoupper((string)$contract->{MeasurementDeviceContract::currency}),
                    'monthly_cost' => $this->addKnownCosts(
                        round($contract->monthlyBasePrice(), 2),
                        $monthlyVariableCost,
                    ),
                    'annual_cost' => $this->addKnownCosts(
                        round($contract->annualBasePrice(), 2),
                        $annualVariableCost,
                    ),
                ];
            })
            ->all();

        return [
            'labels' => array_column($comparison, 'name'),
            'contracts' => $comparison,
        ];
    }

    /**
     * @return array{
     *     contract_name: ?string,
     *     currency: string,
     *     current_unit_price: ?float,
     *     current_unit_price_valid_from: ?string,
     *     base_price: float,
     *     base_price_interval: ?string,
     *     monthly_base_cost: float,
     *     annual_base_cost: float,
     *     estimated_monthly_consumption: ?float,
     *     estimated_annual_consumption: ?float,
     *     monthly_variable_cost: ?float,
     *     annual_variable_cost: ?float,
     *     projected_monthly_cost: ?float,
     *     projected_annual_cost: ?float,
     *     price_count: int
     * }
     */
    public function getOverview(MeasurementDevice $device): array
    {
        $contract = $this->getActiveContract($device);
        $measurementStats = $this->measurementStatistics->getOverview($device);

        if (!$contract instanceof MeasurementDeviceContract) {
            return [
                'contract_name' => null,
                'currency' => 'EUR',
                'current_unit_price' => null,
                'current_unit_price_valid_from' => null,
                'base_price' => 0.0,
                'base_price_interval' => null,
                'monthly_base_cost' => 0.0,
                'annual_base_cost' => 0.0,
                'estimated_monthly_consumption' => null,
                'estimated_annual_consumption' => null,
                'monthly_variable_cost' => null,
                'annual_variable_cost' => null,
                'projected_monthly_cost' => null,
                'projected_annual_cost' => null,
                'price_count' => 0,
            ];
        }

        $today = CarbonImmutable::today();
        $currentPrice = $contract->currentPrice($today);
        $dailyConsumption = $this->estimatedDailyConsumption($measurementStats);
        $monthlyConsumption = $dailyConsumption === null
            ? null
            : round($dailyConsumption * $today->daysInMonth, 2);
        $annualConsumption = $dailyConsumption === null
            ? null
            : round($dailyConsumption * 365, 2);
        $unitPrice = $currentPrice ? (float)$currentPrice->{MeasurementDeviceContractPrice::unit_price} : null;
        $monthlyVariableCost = $monthlyConsumption !== null && $unitPrice !== null
            ? round($monthlyConsumption * $unitPrice, 2)
            : null;
        $annualVariableCost = $annualConsumption !== null && $unitPrice !== null
            ? round($annualConsumption * $unitPrice, 2)
            : null;
        $monthlyBaseCost = round($contract->monthlyBasePrice(), 2);
        $annualBaseCost = round($contract->annualBasePrice(), 2);

        return [
            'contract_name' => (string)$contract->{MeasurementDeviceContract::name},
            'currency' => strtoupper((string)$contract->{MeasurementDeviceContract::currency}),
            'current_unit_price' => $unitPrice,
            'current_unit_price_valid_from' => $currentPrice?->{MeasurementDeviceContractPrice::valid_from}?->toDateString(),
            'base_price' => (float)($contract->{MeasurementDeviceContract::base_price} ?? 0),
            'base_price_interval' => $contract->{MeasurementDeviceContract::base_price_interval},
            'monthly_base_cost' => $monthlyBaseCost,
            'annual_base_cost' => $annualBaseCost,
            'estimated_monthly_consumption' => $monthlyConsumption,
            'estimated_annual_consumption' => $annualConsumption,
            'monthly_variable_cost' => $monthlyVariableCost,
            'annual_variable_cost' => $annualVariableCost,
            'projected_monthly_cost' => $this->addKnownCosts($monthlyBaseCost, $monthlyVariableCost),
            'projected_annual_cost' => $this->addKnownCosts($annualBaseCost, $annualVariableCost),
            'price_count' => $contract->prices->count(),
        ];
    }

    private function getActiveContract(MeasurementDevice $device): ?MeasurementDeviceContract
    {
        $contract = $device->relationLoaded(MeasurementDevice::has_one_active_contract)
            ? $device->activeContract
            : $device->activeContract()->first();

        if (!$contract instanceof MeasurementDeviceContract) {
            return null;
        }

        $contract->loadMissing(MeasurementDeviceContract::has_many_prices);

        return $contract;
    }

    /**
     * @param array{average_daily_consumption_30d?: ?float, average_daily_consumption_90d?: ?float} $stats
     */
    private function estimatedDailyConsumption(array $stats): ?float
    {
        $dailyConsumption = $stats['average_daily_consumption_90d']
            ?? $stats['average_daily_consumption_30d']
            ?? null;

        return $dailyConsumption !== null && $dailyConsumption > 0
            ? (float)$dailyConsumption
            : null;
    }

    private function addKnownCosts(float $baseCost, ?float $variableCost): ?float
    {
        return $variableCost === null && $baseCost <= 0
            ? null
            : round($baseCost + (float)($variableCost ?? 0), 2);
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     costs: array<int, ?float>,
     *     variable_costs: array<int, ?float>,
     *     base_costs: array<int, ?float>,
     *     consumption: array<int, ?float>,
     *     unit_prices: array<int, ?float>,
     *     currency: string,
     *     contract_name: ?string
     * }
     */
    public function getForecast(MeasurementDevice $device, int $months = 12): array
    {
        $months = max(1, min($months, 36));
        $contract = $this->getActiveContract($device);
        $measurementStats = $this->measurementStatistics->getOverview($device);
        $dailyConsumption = $this->estimatedDailyConsumption($measurementStats);
        $start = CarbonImmutable::today()->startOfMonth();
        $labels = [];
        $costs = [];
        $variableCosts = [];
        $baseCosts = [];
        $consumption = [];
        $unitPrices = [];

        for ($offset = 0; $offset < $months; $offset++) {
            $monthStart = $start->addMonths($offset);
            $monthEnd = $monthStart->endOfMonth();
            $labels[] = $monthStart->format('M Y');

            $period = $contract instanceof MeasurementDeviceContract
                ? $this->getCoveredPeriod($contract, $monthStart, $monthEnd)
                : null;

            if (!$period) {
                $costs[] = null;
                $variableCosts[] = null;
                $baseCosts[] = null;
                $consumption[] = null;
                $unitPrices[] = null;

                continue;
            }

            $prices = $contract->prices;
            $periodFrom = $period['from'];
            $periodUntil = $period['until'];
            $coveredDays = $periodFrom->diffInDays($periodUntil) + 1;
            $unitPrice = $this->weightedUnitPrice($prices, $periodFrom, $periodUntil);
            $monthlyConsumption = $dailyConsumption === null
                ? null
                : round($dailyConsumption * $coveredDays, 2);
            $variableCost = $dailyConsumption === null
                ? null
                : $this->variableCostForPeriod($prices, $dailyConsumption, $periodFrom, $periodUntil);
            $baseCost = round($contract->monthlyBasePrice() * $coveredDays / $monthStart->daysInMonth, 2);

            $costs[] = $this->addKnownCosts($baseCost, $variableCost);
            $variableCosts[] = $variableCost;
            $baseCosts[] = $baseCost;
            $consumption[] = $monthlyConsumption;
            $unitPrices[] = $unitPrice;
        }

        return [
            'labels' => $labels,
            'costs' => $costs,
            'variable_costs' => $variableCosts,
            'base_costs' => $baseCosts,
            'consumption' => $consumption,
            'unit_prices' => $unitPrices,
            'currency' => $contract instanceof MeasurementDeviceContract
                ? strtoupper((string)$contract->{MeasurementDeviceContract::currency})
                : 'EUR',
            'contract_name' => $contract instanceof MeasurementDeviceContract
                ? (string)$contract->{MeasurementDeviceContract::name}
                : null,
        ];
    }

    /**
     * @return array{from: CarbonImmutable, until: CarbonImmutable}|null
     */
    private function getCoveredPeriod(
        MeasurementDeviceContract $contract,
        CarbonImmutable           $from,
        CarbonImmutable           $until,
    ): ?array
    {
        $startsOn = $this->toDate($contract->{MeasurementDeviceContract::starts_on});
        $endsOn = $this->toDate($contract->{MeasurementDeviceContract::ends_on});

        if (($startsOn && $startsOn->greaterThan($until))
            || ($endsOn && $endsOn->lessThan($from))) {
            return null;
        }

        return [
            'from' => $startsOn && $startsOn->greaterThan($from) ? $startsOn : $from,
            'until' => $endsOn && $endsOn->lessThan($until) ? $endsOn : $until,
        ];
    }

    private function toDate(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value->startOfDay();
        }

        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::instance($value)->startOfDay();
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return CarbonImmutable::parse($value)->startOfDay();
    }

    /**
     * @param Collection<int, MeasurementDeviceContractPrice> $prices
     */
    private function weightedUnitPrice(
        Collection      $prices,
        CarbonImmutable $from,
        CarbonImmutable $until,
    ): ?float
    {
        $weightedPrice = 0.0;
        $coveredDays = 0;

        for ($date = $from; $date->lessThanOrEqualTo($until); $date = $date->addDay()) {
            $price = $this->priceForDate($prices, $date);
            if ($price === null) {
                continue;
            }

            $weightedPrice += $price * 1;
            $coveredDays++;
        }

        return $coveredDays > 0 ? round($weightedPrice / $coveredDays, 6) : null;
    }

    /**
     * @param Collection<int, MeasurementDeviceContractPrice> $prices
     */
    private function priceForDate(Collection $prices, CarbonImmutable $date): ?float
    {
        $selectedPrice = null;
        $selectedValidFrom = null;

        foreach ($prices as $price) {
            $validFrom = $this->toDate($price->{MeasurementDeviceContractPrice::valid_from});
            if (!$validFrom || $validFrom->greaterThan($date)) {
                continue;
            }

            if ($selectedValidFrom === null || $validFrom->greaterThan($selectedValidFrom)) {
                $selectedValidFrom = $validFrom;
                $selectedPrice = (float)$price->{MeasurementDeviceContractPrice::unit_price};
            }
        }

        return $selectedPrice;
    }

    /**
     * @param Collection<int, MeasurementDeviceContractPrice> $prices
     */
    private function variableCostForPeriod(
        Collection      $prices,
        float           $dailyConsumption,
        CarbonImmutable $from,
        CarbonImmutable $until,
    ): ?float
    {
        $cost = 0.0;
        $pricedDays = 0;

        for ($date = $from; $date->lessThanOrEqualTo($until); $date = $date->addDay()) {
            $price = $this->priceForDate($prices, $date);
            if ($price === null) {
                continue;
            }

            $cost += $dailyConsumption * $price;
            $pricedDays++;
        }

        return $pricedDays > 0 ? round($cost, 2) : null;
    }
}
