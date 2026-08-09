<?php

namespace Tests\Unit;

use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\FixedCost;
use App\Services\FixedCostNextBookingDateCalculator;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class FixedCostNextBookingDateCalculatorTest extends TestCase
{
    public function test_it_advances_overdue_monthly_booking_date_until_future(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE->name,
            FixedCost::next_booking_date => CarbonImmutable::parse('2026-06-15'),
        ]);

        $calculator = new FixedCostNextBookingDateCalculator();
        $result = $calculator->resolveNextBookingDate($fixedCost, CarbonImmutable::parse('2026-07-29'));

        $this->assertNotNull($result);
        $this->assertSame('2026-08-15', $result->toDateString());
    }

    public function test_it_returns_null_when_ends_mode_stops_future_dates(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::interval => FixedCostIntervalEnum::WEEKLY->name,
            FixedCost::ends_mode => FixedCostEndsModeEnum::ENDS->name,
            FixedCost::ends_date => CarbonImmutable::parse('2026-08-01'),
            FixedCost::next_booking_date => CarbonImmutable::parse('2026-07-29'),
        ]);

        $calculator = new FixedCostNextBookingDateCalculator();
        $result = $calculator->resolveNextBookingDate($fixedCost, CarbonImmutable::parse('2026-07-29'));

        $this->assertNull($result);
    }

    public function test_it_switches_to_extended_interval_after_extended_date(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::ends_mode => FixedCostEndsModeEnum::EXTENDED->name,
            FixedCost::extended_date => CarbonImmutable::parse('2026-09-01'),
            FixedCost::extended_interval => FixedCostIntervalEnum::WEEKLY->name,
            FixedCost::next_booking_date => CarbonImmutable::parse('2026-08-15'),
        ]);

        $calculator = new FixedCostNextBookingDateCalculator();
        $result = $calculator->resolveNextBookingDate($fixedCost, CarbonImmutable::parse('2026-09-20'));

        $this->assertNotNull($result);
        $this->assertSame('2026-09-22', $result->toDateString());
    }

    private function makeFixedCost(array $attributes): FixedCost
    {
        $fixedCost = new FixedCost();
        $fixedCost->forceFill([
            FixedCost::name => 'Test fixed cost',
            FixedCost::amount => -100,
            FixedCost::category_id => null,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE->name,
            FixedCost::next_booking_date => CarbonImmutable::parse('2026-07-01'),
        ]);

        foreach ($attributes as $key => $value) {
            $fixedCost->setAttribute($key, $value);
        }

        return $fixedCost;
    }
}

