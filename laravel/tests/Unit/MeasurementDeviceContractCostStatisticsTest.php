<?php

namespace Tests\Unit;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\EnergyTracker\MeasurementDeviceContractPrice;
use App\Services\EnergyTracker\MeasurementDeviceContractCostStatisticsService;
use App\Services\EnergyTracker\MeasurementDeviceStatisticsService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MeasurementDeviceContractCostStatisticsTest extends TestCase
{
    public function test_it_calculates_monthly_and_annual_costs_from_the_active_contract(): void
    {
        Carbon::setTestNow(CarbonImmutable::parse('2026-09-15'));

        $contract = $this->makeContract([
            MeasurementDeviceContract::base_price => 12,
            MeasurementDeviceContract::base_price_interval => 'MONTHLY',
        ], [
            $this->makePrice(0.30, '2026-01-01'),
        ]);
        $device = $this->makeDevice($contract);
        $service = $this->makeService([
            'average_daily_consumption_90d' => 10.0,
            'average_daily_consumption_30d' => 8.0,
        ]);

        $overview = $service->getOverview($device);

        $this->assertSame('Testvertrag', $overview['contract_name']);
        $this->assertSame('EUR', $overview['currency']);
        $this->assertSame(0.30, $overview['current_unit_price']);
        $this->assertSame(12.0, $overview['monthly_base_cost']);
        $this->assertSame(12.0, $overview['base_price']);
        $this->assertSame(300.0, $overview['estimated_monthly_consumption']);
        $this->assertSame(90.0, $overview['monthly_variable_cost']);
        $this->assertSame(102.0, $overview['projected_monthly_cost']);
        $this->assertSame(1239.0, $overview['projected_annual_cost']);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<int, MeasurementDeviceContractPrice> $prices
     */
    private function makeContract(array $attributes, array $prices): MeasurementDeviceContract
    {
        $contract = new MeasurementDeviceContract(array_merge([
            MeasurementDeviceContract::name => 'Testvertrag',
            MeasurementDeviceContract::currency => 'EUR',
            MeasurementDeviceContract::is_active => true,
        ], $attributes));
        $contract->setRelation(
            MeasurementDeviceContract::has_many_prices,
            new Collection($prices),
        );

        return $contract;
    }

    private function makePrice(float $unitPrice, string $validFrom): MeasurementDeviceContractPrice
    {
        return new MeasurementDeviceContractPrice([
            MeasurementDeviceContractPrice::unit_price => $unitPrice,
            MeasurementDeviceContractPrice::valid_from => $validFrom,
        ]);
    }

    private function makeDevice(?MeasurementDeviceContract $contract): MeasurementDevice
    {
        $device = new MeasurementDevice;
        $device->setRelation(MeasurementDevice::has_one_active_contract, $contract);

        return $device;
    }

    /**
     * @param array<string, mixed> $stats
     */
    private function makeService(array $stats): MeasurementDeviceContractCostStatisticsService
    {
        $measurementStatistics = new class($stats) extends MeasurementDeviceStatisticsService {
            /**
             * @param array<string, mixed> $stats
             */
            public function __construct(private readonly array $stats)
            {
            }

            public function getOverview(MeasurementDevice $device): array
            {
                return $this->stats;
            }
        };

        return new MeasurementDeviceContractCostStatisticsService($measurementStatistics);
    }

    public function test_it_applies_price_changes_to_the_monthly_forecast(): void
    {
        Carbon::setTestNow(CarbonImmutable::parse('2026-09-15'));

        $contract = $this->makeContract([], [
            $this->makePrice(0.30, '2026-01-01'),
            $this->makePrice(0.60, '2026-10-15'),
        ]);
        $device = $this->makeDevice($contract);
        $service = $this->makeService([
            'average_daily_consumption_90d' => 1.0,
            'average_daily_consumption_30d' => 1.0,
        ]);

        $forecast = $service->getForecast($device, 2);

        $this->assertSame(['Sep 2026', 'Oct 2026'], $forecast['labels']);
        $this->assertEqualsWithDelta(9.0, $forecast['variable_costs'][0], 0.0001);
        $this->assertEqualsWithDelta(14.4, $forecast['variable_costs'][1], 0.0001);
        $this->assertEqualsWithDelta(14.4, $forecast['costs'][1], 0.0001);
    }

    public function test_it_returns_unknown_projected_costs_without_an_active_contract_or_usage_data(): void
    {
        $device = $this->makeDevice(null);
        $service = $this->makeService([
            'average_daily_consumption_90d' => null,
            'average_daily_consumption_30d' => null,
        ]);

        $overview = $service->getOverview($device);
        $forecast = $service->getForecast($device, 1);

        $this->assertNull($overview['projected_monthly_cost']);
        $this->assertNull($overview['projected_annual_cost']);
        $this->assertNull($forecast['costs'][0]);
    }

    public function test_it_compares_all_contracts_using_the_current_usage_estimate(): void
    {
        Carbon::setTestNow(CarbonImmutable::parse('2026-09-15'));

        $activeContract = $this->makeContract([
            MeasurementDeviceContract::name => 'Aktiver Vertrag',
            MeasurementDeviceContract::base_price => 10,
        ], [
            $this->makePrice(0.30, '2026-01-01'),
        ]);
        $inactiveContract = $this->makeContract([
            MeasurementDeviceContract::name => 'Alter Vertrag',
            MeasurementDeviceContract::base_price => 5,
            MeasurementDeviceContract::is_active => false,
        ], [
            $this->makePrice(0.40, '2026-01-01'),
        ]);
        $device = $this->makeDevice($activeContract);
        $device->setRelation(MeasurementDevice::has_many_contracts, collect([
            $activeContract,
            $inactiveContract,
        ]));
        $service = $this->makeService([
            'average_daily_consumption_90d' => 1.0,
            'average_daily_consumption_30d' => 1.0,
        ]);

        $comparison = $service->getContractComparison($device);

        $this->assertSame(['Aktiver Vertrag', 'Alter Vertrag'], $comparison['labels']);
        $this->assertSame(19.0, $comparison['contracts'][0]['monthly_cost']);
        $this->assertSame(17.0, $comparison['contracts'][1]['monthly_cost']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
