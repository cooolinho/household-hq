<?php

namespace Tests\Unit;

use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostIntervalUnitEnum;
use App\Models\Financial\FixedCost;
use App\Services\FixedCostNextBookingDateCalculator;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
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

        $calculator = new FixedCostNextBookingDateCalculator;
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

        $calculator = new FixedCostNextBookingDateCalculator;
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

        $calculator = new FixedCostNextBookingDateCalculator;
        $result = $calculator->resolveNextBookingDate($fixedCost, CarbonImmutable::parse('2026-09-20'));

        $this->assertNotNull($result);
        $this->assertSame('2026-09-22', $result->toDateString());
    }

    public function test_it_supports_custom_intervals_in_both_directions(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::interval => FixedCostIntervalEnum::CUSTOM->name,
            FixedCost::custom_interval_value => 4,
            FixedCost::custom_interval_unit => FixedCostIntervalUnitEnum::MONTH->name,
        ]);
        $calculator = new FixedCostNextBookingDateCalculator;
        $anchor = CarbonImmutable::parse('2026-11-01');

        $this->assertSame('2027-03-01', $calculator->addInterval($fixedCost, $anchor)->toDateString());
        $this->assertSame('2026-07-01', $calculator->subtractInterval($fixedCost, $anchor)->toDateString());
    }

    public function test_it_supports_day_week_and_year_custom_units(): void
    {
        $calculator = new FixedCostNextBookingDateCalculator;
        $anchor = CarbonImmutable::parse('2026-09-05');

        foreach ([
                     [FixedCostIntervalUnitEnum::DAY->name, '2026-09-08'],
                     [FixedCostIntervalUnitEnum::WEEK->name, '2026-09-26'],
                     [FixedCostIntervalUnitEnum::YEAR->name, '2027-09-05'],
                 ] as [$unit, $expectedDate]) {
            $fixedCost = $this->makeFixedCost([
                FixedCost::interval => FixedCostIntervalEnum::CUSTOM->name,
                FixedCost::custom_interval_value => $unit === FixedCostIntervalUnitEnum::YEAR->name ? 1 : 3,
                FixedCost::custom_interval_unit => $unit,
            ]);

            $this->assertSame($expectedDate, $calculator->addInterval($fixedCost, $anchor)->toDateString());
        }
    }

    public function test_it_uses_custom_extended_interval_after_extension_date(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::ends_mode => FixedCostEndsModeEnum::EXTENDED->name,
            FixedCost::extended_date => CarbonImmutable::parse('2026-09-01'),
            FixedCost::extended_interval => FixedCostIntervalEnum::CUSTOM->name,
            FixedCost::custom_extended_interval_value => 4,
            FixedCost::custom_extended_interval_unit => FixedCostIntervalUnitEnum::MONTH->name,
        ]);
        $calculator = new FixedCostNextBookingDateCalculator;

        $this->assertSame(
            '2027-01-01',
            $calculator->addInterval($fixedCost, CarbonImmutable::parse('2026-09-01'))->toDateString(),
        );
        $this->assertSame(
            '2026-09-15',
            $calculator->addInterval($fixedCost, CarbonImmutable::parse('2026-08-15'))->toDateString(),
        );
    }

    public function test_it_rejects_incomplete_custom_intervals(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::interval => FixedCostIntervalEnum::CUSTOM->name,
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new FixedCostNextBookingDateCalculator)->addInterval(
            $fixedCost,
            CarbonImmutable::parse('2026-09-05'),
        );
    }

    private function makeFixedCost(array $attributes): FixedCost
    {
        $fixedCost = new FixedCost;
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
