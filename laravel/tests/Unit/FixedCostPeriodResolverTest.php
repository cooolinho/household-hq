<?php

namespace Tests\Unit;

use App\Services\FixedCost\FixedCostPeriodResolver;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class FixedCostPeriodResolverTest extends TestCase
{
    public function test_start_day_one_resolves_to_the_calendar_month(): void
    {
        $range = (new FixedCostPeriodResolver())->resolve(1, CarbonImmutable::parse('2026-09-15'));

        $this->assertSame('2026-09-01', $range['start']->toDateString());
        $this->assertSame('2026-09-30', $range['end']->toDateString());
    }

    public function test_start_day_after_today_shifts_the_period_to_the_previous_month(): void
    {
        // Heute ist der 10., Periodenstart 25. -> die laufende Periode begann bereits am 25. des Vormonats.
        $range = (new FixedCostPeriodResolver())->resolve(25, CarbonImmutable::parse('2026-09-10'));

        $this->assertSame('2026-08-25', $range['start']->toDateString());
        $this->assertSame('2026-09-24', $range['end']->toDateString());
    }

    public function test_start_day_on_or_before_today_keeps_the_period_in_the_current_month(): void
    {
        // Heute ist der 27., Periodenstart 25. -> die laufende Periode begann bereits in diesem Monat.
        $range = (new FixedCostPeriodResolver())->resolve(25, CarbonImmutable::parse('2026-09-27'));

        $this->assertSame('2026-09-25', $range['start']->toDateString());
        $this->assertSame('2026-10-24', $range['end']->toDateString());
    }

    public function test_start_day_thirty_one_is_clamped_in_february(): void
    {
        $range = (new FixedCostPeriodResolver())->resolve(31, CarbonImmutable::parse('2026-02-10'));

        $this->assertSame('2026-01-31', $range['start']->toDateString());
        $this->assertSame('2026-02-27', $range['end']->toDateString());
    }

    public function test_out_of_range_days_are_clamped(): void
    {
        $range = (new FixedCostPeriodResolver())->resolve(0, CarbonImmutable::parse('2026-09-15'));
        $this->assertSame('2026-09-01', $range['start']->toDateString());
        $this->assertSame('2026-09-30', $range['end']->toDateString());

        $range = (new FixedCostPeriodResolver())->resolve(99, CarbonImmutable::parse('2026-09-15'));
        $this->assertNotNull($range['start']);
        $this->assertNotNull($range['end']);
    }
}
