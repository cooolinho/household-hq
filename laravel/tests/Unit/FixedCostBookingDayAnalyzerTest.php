<?php

namespace Tests\Unit;

use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostIntervalUnitEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use App\Services\FixedCost\FixedCostBookingDayAnalyzer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

class FixedCostBookingDayAnalyzerTest extends TestCase
{
    public function test_it_uses_lower_median_for_even_transaction_count(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::next_booking_date => CarbonImmutable::parse('2026-09-15'),
        ]);
        $transactions = $this->makeTransactions(['2026-06-10', '2026-07-12', '2026-08-14', '2026-09-16']);

        $analysis = (new FixedCostBookingDayAnalyzer())->analyze(
            $fixedCost,
            $transactions,
            minOccurrences: 3,
            analyzedFrom: CarbonImmutable::parse('2026-06-01'),
            analyzedTo: CarbonImmutable::parse('2026-09-20'),
        );

        // Sortiert: 10, 12, 14, 16 -> unterer Median = 12 (Index 1 von 0..3)
        $this->assertNotNull($analysis);
        $this->assertSame(12, $analysis->suggestedDay);
        $this->assertSame(15, $analysis->currentDay);
        $this->assertSame(3, $analysis->deviationDays);
        $this->assertSame(4, $analysis->sampleCount);
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
            FixedCost::next_booking_date => CarbonImmutable::parse('2026-09-15'),
        ]);

        foreach ($attributes as $key => $value) {
            $fixedCost->setAttribute($key, $value);
        }

        return $fixedCost;
    }

    /**
     * @param list<string> $dates
     * @return Collection<int, Transaction>
     */
    private function makeTransactions(array $dates): Collection
    {
        return collect($dates)->map(function (string $date): Transaction {
            $transaction = new Transaction;
            $transaction->forceFill([
                Transaction::date => $date,
                Transaction::amount => -50,
            ]);

            return $transaction;
        });
    }

    public function test_it_uses_exact_median_for_odd_transaction_count(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::next_booking_date => CarbonImmutable::parse('2026-09-01'),
        ]);
        $transactions = $this->makeTransactions(['2026-07-05', '2026-08-05', '2026-09-05']);

        $analysis = (new FixedCostBookingDayAnalyzer())->analyze(
            $fixedCost,
            $transactions,
            minOccurrences: 3,
            analyzedFrom: CarbonImmutable::parse('2026-07-01'),
            analyzedTo: CarbonImmutable::parse('2026-09-10'),
        );

        $this->assertNotNull($analysis);
        $this->assertSame(5, $analysis->suggestedDay);
        $this->assertSame(4, $analysis->deviationDays);
    }

    public function test_it_computes_cyclic_deviation_across_month_boundary(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::next_booking_date => CarbonImmutable::parse('2026-08-01'),
        ]);
        $transactions = $this->makeTransactions(['2026-05-31', '2026-06-30', '2026-07-31']);

        $analysis = (new FixedCostBookingDayAnalyzer())->analyze(
            $fixedCost,
            $transactions,
            minOccurrences: 3,
            analyzedFrom: CarbonImmutable::parse('2026-05-01'),
            analyzedTo: CarbonImmutable::parse('2026-08-05'),
        );

        // Suggested Tag 31 vs. aktueller Tag 1 -> nur 1 Tag Abweichung (über den Monatswechsel), nicht 30.
        $this->assertNotNull($analysis);
        $this->assertSame(31, $analysis->suggestedDay);
        $this->assertSame(1, $analysis->deviationDays);
    }

    public function test_it_returns_null_when_below_minimum_occurrences(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::next_booking_date => CarbonImmutable::parse('2026-09-15'),
        ]);
        $transactions = $this->makeTransactions(['2026-08-10', '2026-09-16']);

        $analysis = (new FixedCostBookingDayAnalyzer())->analyze(
            $fixedCost,
            $transactions,
            minOccurrences: 3,
            analyzedFrom: CarbonImmutable::parse('2026-08-01'),
            analyzedTo: CarbonImmutable::parse('2026-09-20'),
        );

        $this->assertNull($analysis);
    }

    public function test_it_returns_null_for_weekly_interval(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::interval => FixedCostIntervalEnum::WEEKLY->name,
            FixedCost::next_booking_date => CarbonImmutable::parse('2026-09-15'),
        ]);
        $transactions = $this->makeTransactions(['2026-08-25', '2026-09-01', '2026-09-08']);

        $analysis = (new FixedCostBookingDayAnalyzer())->analyze(
            $fixedCost,
            $transactions,
            minOccurrences: 3,
            analyzedFrom: CarbonImmutable::parse('2026-08-01'),
            analyzedTo: CarbonImmutable::parse('2026-09-20'),
        );

        $this->assertNull($analysis);
    }

    public function test_it_returns_null_for_custom_day_based_interval(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::interval => FixedCostIntervalEnum::CUSTOM->name,
            FixedCost::custom_interval_value => 10,
            FixedCost::custom_interval_unit => FixedCostIntervalUnitEnum::DAY->name,
            FixedCost::next_booking_date => CarbonImmutable::parse('2026-09-15'),
        ]);
        $transactions = $this->makeTransactions(['2026-08-25', '2026-09-01', '2026-09-08']);

        $analysis = (new FixedCostBookingDayAnalyzer())->analyze(
            $fixedCost,
            $transactions,
            minOccurrences: 3,
            analyzedFrom: CarbonImmutable::parse('2026-08-01'),
            analyzedTo: CarbonImmutable::parse('2026-09-20'),
        );

        $this->assertNull($analysis);
    }

    public function test_it_analyzes_custom_month_based_interval(): void
    {
        $fixedCost = $this->makeFixedCost([
            FixedCost::interval => FixedCostIntervalEnum::CUSTOM->name,
            FixedCost::custom_interval_value => 2,
            FixedCost::custom_interval_unit => FixedCostIntervalUnitEnum::MONTH->name,
            FixedCost::next_booking_date => CarbonImmutable::parse('2026-09-01'),
        ]);
        $transactions = $this->makeTransactions(['2026-05-05', '2026-07-05', '2026-09-05']);

        $analysis = (new FixedCostBookingDayAnalyzer())->analyze(
            $fixedCost,
            $transactions,
            minOccurrences: 3,
            analyzedFrom: CarbonImmutable::parse('2026-05-01'),
            analyzedTo: CarbonImmutable::parse('2026-09-10'),
        );

        $this->assertNotNull($analysis);
        $this->assertSame(5, $analysis->suggestedDay);
    }
}
