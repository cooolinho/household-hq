<?php

namespace Tests\Unit;

use App\Models\Enums\BudgetPeriodEnum;
use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostIntervalUnitEnum;
use App\Models\Financial\Budget;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostCategory;
use App\Models\User;
use App\Services\FixedCost\FixedCostBalanceService;
use App\Services\FixedCost\FixedCostCategoryShare;
use App\Services\FixedCost\FixedCostPeriodBucket;
use App\Services\FixedCost\ProjectedBooking;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedCostBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): FixedCostBalanceService
    {
        return app(FixedCostBalanceService::class);
    }

    public function test_it_sums_fixed_costs_into_monthly_and_yearly_balance(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-15',
        ]);

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Gehalt',
            FixedCost::amount => 2000,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-01',
        ]);

        $balance = $this->service()->balanceForUser($user->id, includeBudgets: false);

        self::assertSame(2000.0, $balance->monthlyIncome);
        self::assertSame(700.0, $balance->monthlyFixedCostExpenses);
        self::assertSame(24000.0, $balance->yearlyIncome);
        self::assertSame(8400.0, $balance->yearlyFixedCostExpenses);
    }

    public function test_it_aggregates_flagged_budgets_into_one_monthly_expense(): void
    {
        $user = User::factory()->create();

        $this->createBudget($user->id, 300, BudgetPeriodEnum::MONTHLY);
        $this->createBudget($user->id, 900, BudgetPeriodEnum::QUARTERLY);
        $this->createBudget($user->id, 1200, BudgetPeriodEnum::YEARLY);

        $balance = $this->service()->balanceForUser($user->id, includeBudgets: true);

        self::assertEqualsWithDelta(700.0, $balance->monthlyBudgetExpenses, 0.0001);
        self::assertEqualsWithDelta(8400.0, $balance->yearlyBudgetExpenses, 0.0001);
        self::assertSame(3, $balance->budgetCount);
    }

    public function test_the_requirement_example_produces_one_thousand_euro_expenses(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-15',
        ]);

        $this->createBudget($user->id, 300, BudgetPeriodEnum::MONTHLY);

        $balance = $this->service()->balanceForUser($user->id, includeBudgets: true);

        self::assertSame(1000.0, $balance->monthlyExpenses());
    }

    public function test_it_ignores_budgets_that_are_inactive_or_not_flagged(): void
    {
        $user = User::factory()->create();

        $this->createBudget($user->id, 300, BudgetPeriodEnum::MONTHLY, active: false);
        $this->createBudget($user->id, 500, BudgetPeriodEnum::MONTHLY, includeInBalance: false);

        $balance = $this->service()->balanceForUser($user->id, includeBudgets: true);

        self::assertSame(0.0, $balance->monthlyBudgetExpenses);
        self::assertSame(0, $balance->budgetCount);
    }

    public function test_it_ignores_budgets_in_another_currency(): void
    {
        $user = User::factory()->create();

        $this->createBudget($user->id, 300, BudgetPeriodEnum::MONTHLY, currency: 'USD');

        $balance = $this->service()->balanceForUser($user->id, includeBudgets: true, currency: 'EUR');

        self::assertSame(0.0, $balance->monthlyBudgetExpenses);
    }

    public function test_excluding_budgets_removes_them_from_the_balance(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-15',
        ]);

        $this->createBudget($user->id, 300, BudgetPeriodEnum::MONTHLY);

        $excluded = $this->service()->balanceForUser($user->id, includeBudgets: false);
        self::assertSame(700.0, $excluded->monthlyExpenses());

        $included = $this->service()->balanceForUser($user->id, includeBudgets: true);
        self::assertSame(700.0, $included->withBudgets(false)->monthlyExpenses());
    }

    public function test_it_reports_fixed_costs_with_a_broken_custom_interval_instead_of_throwing(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Kaputt',
            FixedCost::amount => -50,
            FixedCost::interval => FixedCostIntervalEnum::CUSTOM->name,
            FixedCost::custom_interval_value => null,
            FixedCost::custom_interval_unit => null,
            FixedCost::next_booking_date => '2026-09-15',
        ]);

        $balance = $this->service()->balanceForUser($user->id);

        self::assertTrue($balance->hasInvalidFixedCosts());
        self::assertCount(1, $balance->invalidFixedCosts);
    }

    public function test_it_projects_bookings_across_a_range_using_the_interval_calculator(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-11-15',
        ]);

        $bookings = $this->service()->projectedBookings(
            $user->id,
            CarbonImmutable::parse('2026-09-01'),
            CarbonImmutable::parse('2027-02-28'),
        );

        self::assertCount(6, $bookings);
        self::assertSame('2026-09-15', $bookings[0]->date->toDateString());
        self::assertSame('2027-02-15', $bookings[5]->date->toDateString());

        foreach ($bookings as $index => $booking) {
            self::assertInstanceOf(ProjectedBooking::class, $booking);
            self::assertSame(-700.0, $booking->amount);
        }
    }

    public function test_it_stops_projecting_when_the_end_mode_is_reached(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Befristet',
            FixedCost::amount => -50,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::ends_mode => FixedCostEndsModeEnum::ENDS->name,
            FixedCost::ends_date => '2026-12-31',
            FixedCost::next_booking_date => '2026-11-15',
        ]);

        $bookings = $this->service()->projectedBookings(
            $user->id,
            CarbonImmutable::parse('2026-09-01'),
            CarbonImmutable::parse('2027-03-31'),
        );

        $dates = array_map(fn(ProjectedBooking $b): string => $b->date->toDateString(), $bookings);

        self::assertContains('2026-12-15', $dates);
        foreach ($dates as $date) {
            self::assertLessThanOrEqual('2026-12-31', $date);
        }
    }

    public function test_it_does_not_project_bookings_before_the_record_was_created(): void
    {
        $user = User::factory()->create();

        $fixedCost = FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Neu',
            FixedCost::amount => -50,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-15',
        ]);

        // created_at liegt nach dem angeblich ersten Buchungstermin im Projektionszeitraum.
        $fixedCost->forceFill([FixedCost::created_at => '2026-08-01 00:00:00'])->saveQuietly();

        $bookings = $this->service()->projectedBookings(
            $user->id,
            CarbonImmutable::parse('2026-01-01'),
            CarbonImmutable::parse('2026-09-30'),
        );

        foreach ($bookings as $booking) {
            self::assertGreaterThanOrEqual('2026-08-01', $booking->date->toDateString());
        }
    }

    public function test_it_buckets_the_trend_by_month_and_adds_normalised_budgets(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-15',
        ]);

        $this->createBudget($user->id, 300, BudgetPeriodEnum::MONTHLY);

        $buckets = $this->service()->trend(
            $user->id,
            CarbonImmutable::parse('2026-09-01'),
            CarbonImmutable::parse('2026-11-30'),
        );

        self::assertCount(3, $buckets);
        self::assertSame(['09.2026', '10.2026', '11.2026'], array_map(fn(FixedCostPeriodBucket $b): string => $b->label, $buckets));

        foreach ($buckets as $bucket) {
            self::assertSame(700.0, $bucket->fixedCostExpenses);
            self::assertEqualsWithDelta(300.0, $bucket->budgetExpenses, 0.0001);
        }
    }

    public function test_it_builds_a_category_distribution_with_a_dedicated_budget_slice(): void
    {
        $user = User::factory()->create();
        $category = FixedCostCategory::query()->create([
            FixedCostCategory::name => 'Wohnen',
        ]);

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::category_id => $category->id,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-15',
        ]);

        $this->createBudget($user->id, 300, BudgetPeriodEnum::MONTHLY);

        $shares = $this->service()->categoryDistribution(
            $user->id,
            CarbonImmutable::parse('2026-09-01'),
            CarbonImmutable::parse('2026-09-30'),
            includeBudgets: true,
        );

        self::assertCount(2, $shares);

        $budgetShare = collect($shares)->first(fn(FixedCostCategoryShare $s): bool => $s->isBudgetAggregate);
        $categoryShare = collect($shares)->first(fn(FixedCostCategoryShare $s): bool => !$s->isBudgetAggregate);

        self::assertNotNull($budgetShare);
        self::assertNotNull($categoryShare);
        self::assertEqualsWithDelta(300.0, $budgetShare->amount, 0.0001);
        self::assertSame(700.0, $categoryShare->amount);
        self::assertSame('Wohnen', $categoryShare->label);
        self::assertSame('Budgets', $budgetShare->label);
    }

    private function createBudget(
        int             $userId,
        float           $amount,
        BudgetPeriodEnum $period,
        bool            $active = true,
        bool            $includeInBalance = true,
        string          $currency = 'EUR',
    ): Budget {
        return Budget::query()->create([
            Budget::user_id => $userId,
            Budget::name => 'Budget',
            Budget::amount => $amount,
            Budget::period => $period->name,
            Budget::currency => $currency,
            Budget::active => $active,
            Budget::include_in_balance => $includeInBalance,
        ]);
    }
}
