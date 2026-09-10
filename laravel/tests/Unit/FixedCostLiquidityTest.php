<?php

namespace Tests\Unit;

use App\Models\Enums\BankAccountTypeEnum;
use App\Models\Enums\BudgetPeriodEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\Budget;
use App\Models\Financial\FixedCost;
use App\Models\User;
use App\Services\FixedCost\FixedCostBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedCostLiquidityTest extends TestCase
{
    use RefreshDatabase;

    public function test_bookings_before_as_of_are_excluded(): void
    {
        $user = User::factory()->create();

        // Bereits am 5. abgebucht -> darf am 15. (asOf) nicht mehr als "ausstehend" zählen.
        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-05',
        ]);

        // Steht noch aus.
        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Strom',
            FixedCost::amount => -100,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-20',
        ]);

        $requirement = $this->service()->liquidityRequirement(
            $user->id,
            CarbonImmutable::parse('2026-09-15'),
            CarbonImmutable::parse('2026-09-30'),
            includeBudgets: false,
        );

        $this->assertSame(100.0, $requirement->requiredAmount);
        $this->assertCount(1, $requirement->bookings);
    }

    private function service(): FixedCostBalanceService
    {
        return app(FixedCostBalanceService::class);
    }

    public function test_income_is_reported_separately_and_not_netted(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Strom',
            FixedCost::amount => -100,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-20',
        ]);

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Gehalt',
            FixedCost::amount => 2000,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-25',
        ]);

        $requirement = $this->service()->liquidityRequirement(
            $user->id,
            CarbonImmutable::parse('2026-09-15'),
            CarbonImmutable::parse('2026-09-30'),
            includeBudgets: false,
        );

        $this->assertSame(100.0, $requirement->requiredAmount);
        $this->assertSame(2000.0, $requirement->expectedIncome);
        // "Noch benötigt" bleibt der Ausgabenbetrag, die Einnahme wird nicht verrechnet.
        $this->assertSame(100.0, $requirement->totalRequired());
    }

    public function test_budget_share_is_prorated_to_the_remaining_days(): void
    {
        $user = User::factory()->create();

        Budget::query()->create([
            Budget::user_id => $user->id,
            Budget::name => 'Lebensmittel',
            Budget::amount => 300,
            Budget::period => BudgetPeriodEnum::MONTHLY->name,
            Budget::currency => 'EUR',
            Budget::active => true,
            Budget::include_in_balance => true,
        ]);

        // asOf 21.09. bis Periodenende 30.09. (September hat 30 Tage) -> 10 von 30 Tagen.
        $requirement = $this->service()->liquidityRequirement(
            $user->id,
            CarbonImmutable::parse('2026-09-21'),
            CarbonImmutable::parse('2026-09-30'),
            includeBudgets: true,
        );

        $this->assertEqualsWithDelta(100.0, $requirement->budgetShare, 0.0001);
    }

    public function test_budget_share_is_zero_when_budgets_are_excluded(): void
    {
        $user = User::factory()->create();

        Budget::query()->create([
            Budget::user_id => $user->id,
            Budget::name => 'Lebensmittel',
            Budget::amount => 300,
            Budget::period => BudgetPeriodEnum::MONTHLY->name,
            Budget::currency => 'EUR',
            Budget::active => true,
            Budget::include_in_balance => true,
        ]);

        $requirement = $this->service()->liquidityRequirement(
            $user->id,
            CarbonImmutable::parse('2026-09-21'),
            CarbonImmutable::parse('2026-09-30'),
            includeBudgets: false,
        );

        $this->assertSame(0.0, $requirement->budgetShare);
    }

    public function test_bank_balance_is_summed_across_accounts_and_flags_the_oldest_snapshot(): void
    {
        $user = User::factory()->create();

        BankAccount::query()->create([
            BankAccount::user_id => $user->id,
            BankAccount::name => 'Giro',
            BankAccount::type => BankAccountTypeEnum::GIRO->name,
            BankAccount::balance => 500,
            BankAccount::balance_date => '2026-09-14',
        ]);

        BankAccount::query()->create([
            BankAccount::user_id => $user->id,
            BankAccount::name => 'Tagesgeld',
            BankAccount::type => BankAccountTypeEnum::SAVINGS->name,
            BankAccount::balance => 1000,
            BankAccount::balance_date => '2026-09-10',
        ]);

        $requirement = $this->service()->liquidityRequirement(
            $user->id,
            CarbonImmutable::parse('2026-09-15'),
            CarbonImmutable::parse('2026-09-30'),
            includeBudgets: false,
        );

        $this->assertSame(1500.0, $requirement->bankBalance);
        $this->assertSame('2026-09-10', $requirement->bankBalanceAsOf?->toDateString());
    }

    public function test_shortfall_and_is_covered_reflect_the_gap_to_the_bank_balance(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -700,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-09-20',
        ]);

        BankAccount::query()->create([
            BankAccount::user_id => $user->id,
            BankAccount::name => 'Giro',
            BankAccount::type => BankAccountTypeEnum::GIRO->name,
            BankAccount::balance => 500,
            BankAccount::balance_date => '2026-09-14',
        ]);

        $requirement = $this->service()->liquidityRequirement(
            $user->id,
            CarbonImmutable::parse('2026-09-15'),
            CarbonImmutable::parse('2026-09-30'),
            includeBudgets: false,
        );

        $this->assertFalse($requirement->isCovered());
        $this->assertEqualsWithDelta(200.0, $requirement->shortfall(), 0.0001);
    }
}
