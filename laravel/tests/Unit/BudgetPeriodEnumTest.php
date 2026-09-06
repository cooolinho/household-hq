<?php

namespace Tests\Unit;

use App\Models\Enums\BudgetPeriodEnum;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class BudgetPeriodEnumTest extends TestCase
{
    public function test_monthly_period_covers_the_calendar_month(): void
    {
        $reference = CarbonImmutable::create(2026, 2, 14, 13, 37);

        self::assertSame('2026-02-01 00:00:00', BudgetPeriodEnum::MONTHLY->periodStart($reference)->toDateTimeString());
        self::assertSame('2026-02-28 23:59:59', BudgetPeriodEnum::MONTHLY->periodEnd($reference)->toDateTimeString());
    }

    public function test_quarterly_period_covers_the_calendar_quarter(): void
    {
        $reference = CarbonImmutable::create(2026, 8, 3, 8, 0);

        self::assertSame('2026-07-01 00:00:00', BudgetPeriodEnum::QUARTERLY->periodStart($reference)->toDateTimeString());
        self::assertSame('2026-09-30 23:59:59', BudgetPeriodEnum::QUARTERLY->periodEnd($reference)->toDateTimeString());
    }

    public function test_yearly_period_covers_the_calendar_year(): void
    {
        $reference = CarbonImmutable::create(2026, 12, 31, 23, 0);

        self::assertSame('2026-01-01 00:00:00', BudgetPeriodEnum::YEARLY->periodStart($reference)->toDateTimeString());
        self::assertSame('2026-12-31 23:59:59', BudgetPeriodEnum::YEARLY->periodEnd($reference)->toDateTimeString());
    }

    public function test_shift_start_moves_across_year_boundaries(): void
    {
        $reference = CarbonImmutable::create(2026, 1, 15);

        self::assertSame('2025-11-01', BudgetPeriodEnum::MONTHLY->shiftStart($reference, -2)->toDateString());
        self::assertSame('2025-04-01', BudgetPeriodEnum::QUARTERLY->shiftStart($reference, -3)->toDateString());
        self::assertSame('2024-01-01', BudgetPeriodEnum::YEARLY->shiftStart($reference, -2)->toDateString());
    }

    public function test_format_range_labels_the_period(): void
    {
        self::assertSame('Q1 2026', BudgetPeriodEnum::QUARTERLY->formatRange(CarbonImmutable::create(2026, 1, 1)));
        self::assertSame('2026', BudgetPeriodEnum::YEARLY->formatRange(CarbonImmutable::create(2026, 1, 1)));
    }

    public function test_options_are_keyed_by_case_name(): void
    {
        self::assertSame([
            'MONTHLY' => 'Monatlich',
            'QUARTERLY' => 'Quartalsweise',
            'YEARLY' => 'Jährlich',
        ], BudgetPeriodEnum::options());
    }
}
