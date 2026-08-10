<?php

namespace Tests\Unit;

use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_transaction_date_and_currency_filter_for_actual_monthly_balance(): void
    {
        $user = User::factory()->create();
        $bank = BankAccount::query()->create([
            BankAccount::name => 'Testkonto',
            BankAccount::user_id => $user->id,
        ]);

        Transaction::query()->create([
            Transaction::date => '2026-08-02',
            Transaction::value_date => '2026-08-02',
            Transaction::payer => 'A',
            Transaction::purpose => 'Einnahme',
            Transaction::balance => 1000,
            Transaction::balance_currency => 'EUR',
            Transaction::amount => 100,
            Transaction::amount_currency => 'EUR',
            Transaction::hash => 'hash-1',
            Transaction::bank_account_id => $bank->id,
            Transaction::user_id => $user->id,
        ]);

        Transaction::query()->create([
            Transaction::date => '2026-08-03',
            Transaction::value_date => '2026-08-03',
            Transaction::payer => 'B',
            Transaction::purpose => 'Ausgabe',
            Transaction::balance => 900,
            Transaction::balance_currency => 'EUR',
            Transaction::amount => -40,
            Transaction::amount_currency => 'EUR',
            Transaction::hash => 'hash-2',
            Transaction::bank_account_id => $bank->id,
            Transaction::user_id => $user->id,
        ]);

        Transaction::query()->create([
            Transaction::date => '2026-08-04',
            Transaction::value_date => '2026-08-04',
            Transaction::payer => 'C',
            Transaction::purpose => 'USD ignored',
            Transaction::balance => 860,
            Transaction::balance_currency => 'USD',
            Transaction::amount => -25,
            Transaction::amount_currency => 'USD',
            Transaction::hash => 'hash-3',
            Transaction::bank_account_id => $bank->id,
            Transaction::user_id => $user->id,
        ]);

        Transaction::query()->create([
            Transaction::date => '2026-07-30',
            Transaction::value_date => '2026-08-02',
            Transaction::payer => 'D',
            Transaction::purpose => 'Date filter',
            Transaction::balance => 850,
            Transaction::balance_currency => 'EUR',
            Transaction::amount => -60,
            Transaction::amount_currency => 'EUR',
            Transaction::hash => 'hash-4',
            Transaction::bank_account_id => $bank->id,
            Transaction::user_id => $user->id,
        ]);

        $data = app(DashboardMetricsService::class)
            ->getMonthlyBalanceData($user->id, 'EUR', CarbonImmutable::parse('2026-08-10'));

        $this->assertSame(100.0, $data['actual']['income']);
        $this->assertSame(40.0, $data['actual']['expenses']);
        $this->assertSame(60.0, $data['actual']['balance']);
    }

    public function test_it_calculates_forecast_monthly_balance_from_fixed_cost_intervals(): void
    {
        $user = User::factory()->create();

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -1200,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-08-15',
        ]);

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Abo',
            FixedCost::amount => -52,
            FixedCost::interval => FixedCostIntervalEnum::WEEKLY->name,
            FixedCost::next_booking_date => '2026-08-11',
        ]);

        FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Rueckerstattung',
            FixedCost::amount => 120,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => '2026-08-20',
        ]);

        $data = app(DashboardMetricsService::class)
            ->getMonthlyBalanceData($user->id, 'EUR', CarbonImmutable::parse('2026-08-10'));

        $expectedExpenses = 1200 + (52 * (52 / 12));

        $this->assertSame(120.0, round($data['forecast']['income'], 2));
        $this->assertSame(round($expectedExpenses, 2), round($data['forecast']['expenses'], 2));
        $this->assertSame(round(120 - $expectedExpenses, 2), round($data['forecast']['balance'], 2));
    }
}

