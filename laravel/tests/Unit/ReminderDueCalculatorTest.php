<?php

namespace Tests\Unit;

use App\Models\ReminderSchedule;
use App\Services\Reminder\ReminderDueCalculator;
use App\Services\Reminder\ReminderTargetRegistry;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ReminderDueCalculatorTest extends TestCase
{
    private ReminderDueCalculator $calculator;

    public function test_it_normalizes_various_date_representations(): void
    {
        $this->assertSame('2026-08-15', $this->calculator->normalizeDate('2026-08-15')->toDateString());
        $this->assertSame('2026-08-15', $this->calculator->normalizeDate(CarbonImmutable::parse('2026-08-15 13:00'))->toDateString());
        $this->assertNull($this->calculator->normalizeDate(null));
        $this->assertNull($this->calculator->normalizeDate(''));
    }

    public function test_it_resolves_a_twelve_hour_offset(): void
    {
        $anchor = CarbonImmutable::parse('2026-08-15 00:00');
        $schedule = $this->schedule(['offset_unit' => 'HOUR', 'offset_value' => 12]);

        $dueAt = $this->calculator->resolveOffsetDueAt($anchor, $schedule, '08:00');

        $this->assertSame('2026-08-14 12:00:00', $dueAt->toDateTimeString());
    }

    private function schedule(array $attributes): ReminderSchedule
    {
        $schedule = new ReminderSchedule();
        $schedule->forceFill($attributes);

        return $schedule;
    }

    public function test_it_resolves_a_three_day_offset_with_default_run_at_time(): void
    {
        $anchor = CarbonImmutable::parse('2026-08-15 00:00');
        $schedule = $this->schedule(['offset_unit' => 'DAY', 'offset_value' => 3]);

        $dueAt = $this->calculator->resolveOffsetDueAt($anchor, $schedule, '08:00');

        $this->assertSame('2026-08-12 08:00:00', $dueAt->toDateTimeString());
    }

    public function test_it_resolves_a_one_week_offset_with_schedule_specific_run_at_time(): void
    {
        $anchor = CarbonImmutable::parse('2026-08-15 00:00');
        $schedule = $this->schedule(['offset_unit' => 'WEEK', 'offset_value' => 1, 'run_at_time' => '10:30']);

        $dueAt = $this->calculator->resolveOffsetDueAt($anchor, $schedule, '08:00');

        $this->assertSame('2026-08-08 10:30:00', $dueAt->toDateTimeString());
    }

    public function test_it_resolves_month_offsets(): void
    {
        $anchor = CarbonImmutable::parse('2026-08-15 00:00');

        $oneMonth = $this->calculator->resolveOffsetDueAt($anchor, $this->schedule(['offset_unit' => 'MONTH', 'offset_value' => 1]), '08:00');
        $twoMonths = $this->calculator->resolveOffsetDueAt($anchor, $this->schedule(['offset_unit' => 'MONTH', 'offset_value' => 2]), '08:00');
        $threeMonths = $this->calculator->resolveOffsetDueAt($anchor, $this->schedule(['offset_unit' => 'MONTH', 'offset_value' => 3]), '08:00');

        $this->assertSame('2026-07-15 08:00:00', $oneMonth->toDateTimeString());
        $this->assertSame('2026-06-15 08:00:00', $twoMonths->toDateTimeString());
        $this->assertSame('2026-05-15 08:00:00', $threeMonths->toDateTimeString());
    }

    public function test_it_returns_null_for_an_incomplete_offset_schedule(): void
    {
        $anchor = CarbonImmutable::parse('2026-08-15 00:00');
        $schedule = $this->schedule(['offset_unit' => null, 'offset_value' => null]);

        $this->assertNull($this->calculator->resolveOffsetDueAt($anchor, $schedule, '08:00'));
    }

    public function test_daily_recurrence_advances_by_one_day(): void
    {
        $schedule = $this->schedule(['recurrence' => 'DAILY', 'start_date' => '2026-08-01']);
        $after = CarbonImmutable::parse('2026-08-05 12:00');

        $next = $this->calculator->resolveNextRecurrence($schedule, $after, '08:00');

        $this->assertSame('2026-08-06 08:00:00', $next->toDateTimeString());
    }

    public function test_every_n_weeks_recurrence(): void
    {
        $schedule = $this->schedule(['recurrence' => 'EVERY_N_WEEKS', 'recurrence_value' => 2, 'start_date' => '2026-08-01']);
        $after = CarbonImmutable::parse('2026-08-01 08:00');

        $next = $this->calculator->resolveNextRecurrence($schedule, $after, '08:00');

        $this->assertSame('2026-08-15 08:00:00', $next->toDateTimeString());
    }

    public function test_monthly_on_day_recurrence(): void
    {
        $schedule = $this->schedule(['recurrence' => 'MONTHLY_ON_DAY', 'recurrence_value' => 1, 'start_date' => '2026-08-15']);
        $after = CarbonImmutable::parse('2026-08-15 08:00');

        $next = $this->calculator->resolveNextRecurrence($schedule, $after, '08:00');

        $this->assertSame('2026-09-01 08:00:00', $next->toDateTimeString());
    }

    public function test_monthly_on_day_recurrence_clamps_day_31_in_february(): void
    {
        $schedule = $this->schedule(['recurrence' => 'MONTHLY_ON_DAY', 'recurrence_value' => 31, 'start_date' => '2027-01-31']);
        $after = CarbonImmutable::parse('2027-01-31 08:00');

        $next = $this->calculator->resolveNextRecurrence($schedule, $after, '08:00');

        // 2027 is not a leap year, February has 28 days.
        $this->assertSame('2027-02-28 08:00:00', $next->toDateTimeString());
    }

    public function test_every_n_months_recurrence(): void
    {
        $schedule = $this->schedule(['recurrence' => 'EVERY_N_MONTHS', 'recurrence_value' => 3, 'start_date' => '2026-01-15']);
        $after = CarbonImmutable::parse('2026-01-15 08:00');

        $next = $this->calculator->resolveNextRecurrence($schedule, $after, '08:00');

        $this->assertSame('2026-04-15 08:00:00', $next->toDateTimeString());
    }

    public function test_quarterly_half_yearly_and_yearly_recurrence(): void
    {
        $after = CarbonImmutable::parse('2026-01-15 08:00');

        $quarterly = $this->calculator->resolveNextRecurrence($this->schedule(['recurrence' => 'QUARTERLY', 'start_date' => '2026-01-15']), $after, '08:00');
        $halfYearly = $this->calculator->resolveNextRecurrence($this->schedule(['recurrence' => 'HALF_YEARLY', 'start_date' => '2026-01-15']), $after, '08:00');
        $yearly = $this->calculator->resolveNextRecurrence($this->schedule(['recurrence' => 'YEARLY', 'start_date' => '2026-01-15']), $after, '08:00');

        $this->assertSame('2026-04-15 08:00:00', $quarterly->toDateTimeString());
        $this->assertSame('2026-07-15 08:00:00', $halfYearly->toDateTimeString());
        $this->assertSame('2027-01-15 08:00:00', $yearly->toDateTimeString());
    }

    public function test_once_recurrence_fires_exactly_once(): void
    {
        $schedule = $this->schedule(['recurrence' => 'ONCE', 'start_date' => '2026-09-20']);

        $beforeStartDate = $this->calculator->resolveNextRecurrence($schedule, CarbonImmutable::parse('2026-09-01'), '08:00');
        $afterFiring = $this->calculator->resolveNextRecurrence($schedule, CarbonImmutable::parse('2026-09-20 08:00'), '08:00');

        $this->assertSame('2026-09-20 08:00:00', $beforeStartDate->toDateTimeString());
        $this->assertNull($afterFiring);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ReminderDueCalculator(new ReminderTargetRegistry());
    }
}
