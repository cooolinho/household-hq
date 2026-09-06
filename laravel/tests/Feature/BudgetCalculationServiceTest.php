<?php

namespace Tests\Feature;

use App\Models\Enums\BankAccountTypeEnum;
use App\Models\Enums\BudgetIconEnum;
use App\Models\Enums\BudgetPeriodEnum;
use App\Models\Enums\BudgetStatusEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\Budget;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\User;
use App\Services\Budget\BudgetCalculationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BudgetCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private const string NOW = '2026-09-10 12:00:00';

    private User $user;

    private BankAccount $bankAccount;

    private TransactionCategory $parentCategory;

    private TransactionCategory $childCategory;

    private BudgetCalculationService $service;

    public function test_spent_amount_sums_expenses_of_linked_categories(): void
    {
        $budget = $this->createBudget([$this->childCategory->id], amount: 500.0);

        $this->createTransaction(-120.50, '2026-09-02', [$this->childCategory->id]);
        $this->createTransaction(-79.50, '2026-09-08', [$this->childCategory->id]);

        $calculation = $this->service->calculate($budget);

        self::assertSame(200.0, $calculation->spent);
        self::assertSame(300.0, $calculation->remaining);
        self::assertSame(40.0, $calculation->percentage);
        self::assertSame(BudgetStatusEnum::OK, $calculation->status);
    }

    /**
     * @param list<int> $categoryIds
     */
    private function createBudget(array $categoryIds, float $amount, string $name = 'Testbudget'): Budget
    {
        $budget = Budget::query()->create([
            Budget::user_id => $this->user->id,
            Budget::name => $name,
            Budget::icon => BudgetIconEnum::default(),
            Budget::amount => $amount,
            Budget::currency => Budget::DEFAULT_CURRENCY,
            Budget::period => BudgetPeriodEnum::MONTHLY->name,
            Budget::include_subcategories => true,
        ]);

        $budget->transactionCategories()->sync($categoryIds);

        return $budget;
    }

    /**
     * @param list<int> $categoryIds
     */
    private function createTransaction(
        float  $amount,
        string $date,
        array  $categoryIds,
        ?int   $userId = null,
        string $currency = 'EUR',
    ): Transaction
    {
        $transaction = Transaction::factory()
            ->forUser($userId ?? (int)$this->user->id)
            ->forBankAccount((int)$this->bankAccount->id)
            ->create([
                Transaction::date => $date,
                Transaction::value_date => $date,
                Transaction::amount => $amount,
                Transaction::amount_currency => $currency,
            ]);

        $transaction->transactionCategories()->sync($categoryIds);

        return $transaction;
    }

    public function test_transactions_outside_the_period_are_ignored(): void
    {
        $budget = $this->createBudget([$this->childCategory->id], amount: 500.0);

        $this->createTransaction(-100.0, '2026-09-01', [$this->childCategory->id]);
        $this->createTransaction(-999.0, '2026-08-31', [$this->childCategory->id]);
        $this->createTransaction(-999.0, '2026-10-01', [$this->childCategory->id]);

        self::assertSame(100.0, $this->service->calculate($budget)->spent);
    }

    public function test_subcategories_are_only_included_when_the_toggle_is_active(): void
    {
        $budget = $this->createBudget([$this->parentCategory->id], amount: 500.0);

        $this->createTransaction(-50.0, '2026-09-03', [$this->parentCategory->id]);
        $this->createTransaction(-70.0, '2026-09-04', [$this->childCategory->id]);

        self::assertSame(120.0, $this->service->calculate($budget)->spent);

        $budget->forceFill([Budget::include_subcategories => false])->save();
        $budget->unsetRelation(Budget::belongs_to_many_transaction_categories);

        self::assertSame(50.0, $this->service->calculate($budget)->spent);
    }

    public function test_a_transaction_in_two_linked_categories_is_only_counted_once(): void
    {
        $budget = $this->createBudget([$this->parentCategory->id, $this->childCategory->id], amount: 500.0);

        $this->createTransaction(-80.0, '2026-09-05', [
            $this->parentCategory->id,
            $this->childCategory->id,
        ]);

        self::assertSame(80.0, $this->service->calculate($budget)->spent);
    }

    public function test_refunds_reduce_the_spent_amount_and_never_go_below_zero(): void
    {
        $budget = $this->createBudget([$this->childCategory->id], amount: 500.0);

        $this->createTransaction(-100.0, '2026-09-02', [$this->childCategory->id]);
        $this->createTransaction(30.0, '2026-09-03', [$this->childCategory->id]);

        self::assertSame(70.0, $this->service->calculate($budget)->spent);

        $this->createTransaction(500.0, '2026-09-04', [$this->childCategory->id]);

        self::assertSame(0.0, $this->service->calculate($budget)->spent);
    }

    public function test_transactions_of_other_users_and_currencies_are_ignored(): void
    {
        $budget = $this->createBudget([$this->childCategory->id], amount: 500.0);
        $otherUser = User::factory()->create();

        $this->createTransaction(-100.0, '2026-09-02', [$this->childCategory->id]);
        $this->createTransaction(-999.0, '2026-09-02', [$this->childCategory->id], userId: $otherUser->id);
        $this->createTransaction(-999.0, '2026-09-02', [$this->childCategory->id], currency: 'USD');

        self::assertSame(100.0, $this->service->calculate($budget)->spent);
    }

    public function test_status_follows_the_configured_thresholds(): void
    {
        $budget = $this->createBudget([$this->childCategory->id], amount: 100.0);

        $this->createTransaction(-79.0, '2026-09-02', [$this->childCategory->id]);
        self::assertSame(BudgetStatusEnum::OK, $this->service->calculate($budget)->status);

        $this->createTransaction(-1.0, '2026-09-03', [$this->childCategory->id]);
        self::assertSame(BudgetStatusEnum::WARNING, $this->service->calculate($budget)->status);

        $this->createTransaction(-20.0, '2026-09-04', [$this->childCategory->id]);
        self::assertSame(BudgetStatusEnum::EXCEEDED, $this->service->calculate($budget)->status);
    }

    public function test_forecast_projects_the_period_and_the_exceeding_date(): void
    {
        // 10. September: 10 von 30 Tagen vorbei, 100 € verbraucht -> 10 €/Tag -> 300 € Hochrechnung.
        $budget = $this->createBudget([$this->childCategory->id], amount: 200.0);
        $this->createTransaction(-100.0, '2026-09-05', [$this->childCategory->id]);

        $calculation = $this->service->calculate($budget);

        self::assertSame(30, $calculation->daysTotal);
        self::assertSame(10, $calculation->daysElapsed);
        self::assertSame(20, $calculation->daysRemaining);
        self::assertSame(10.0, $calculation->dailyAverage);
        self::assertSame(300.0, $calculation->projected);
        self::assertSame(5.0, $calculation->dailyAllowance);
        self::assertTrue($calculation->isProjectedToExceed());
        self::assertSame('2026-09-20', $calculation->projectedExceededAt?->toDateString());
    }

    public function test_forecast_has_no_exceeding_date_when_the_limit_is_already_reached(): void
    {
        $budget = $this->createBudget([$this->childCategory->id], amount: 50.0);
        $this->createTransaction(-60.0, '2026-09-05', [$this->childCategory->id]);

        $calculation = $this->service->calculate($budget);

        self::assertNull($calculation->projectedExceededAt);
        self::assertTrue($calculation->isOverspent());
        self::assertSame(-10.0, $calculation->remaining);
    }

    public function test_a_budget_without_categories_has_no_spending(): void
    {
        $budget = $this->createBudget([], amount: 100.0);
        $this->createTransaction(-100.0, '2026-09-02', [$this->childCategory->id]);

        self::assertSame(0.0, $this->service->calculate($budget)->spent);
    }

    public function test_history_returns_one_entry_per_period(): void
    {
        $budget = $this->createBudget([$this->childCategory->id], amount: 100.0);

        $this->createTransaction(-40.0, '2026-07-10', [$this->childCategory->id]);
        $this->createTransaction(-25.0, '2026-09-01', [$this->childCategory->id]);

        $history = $this->service->history($budget, 3);

        self::assertCount(3, $history);
        self::assertSame(['Juli 2026', 'August 2026', 'September 2026'], array_column($history, 'label'));
        self::assertSame([40.0, 0.0, 25.0], array_column($history, 'spent'));
    }

    public function test_calculate_for_user_only_returns_own_active_budgets(): void
    {
        $active = $this->createBudget([$this->childCategory->id], amount: 100.0);
        $inactive = $this->createBudget([$this->childCategory->id], amount: 100.0, name: 'Inaktiv');
        $inactive->forceFill([Budget::active => false])->save();

        $otherUser = User::factory()->create();
        Budget::query()->create([
            Budget::user_id => $otherUser->id,
            Budget::name => 'Fremd',
            Budget::icon => BudgetIconEnum::default(),
            Budget::amount => 100.0,
            Budget::period => BudgetPeriodEnum::default(),
        ]);

        $calculations = $this->service->calculateForUser((int)$this->user->id);

        self::assertCount(1, $calculations);
        self::assertSame($active->id, $calculations[0]->budget->id);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::NOW));
        CarbonImmutable::setTestNow(CarbonImmutable::parse(self::NOW));

        $this->user = User::factory()->create();
        $this->service = app(BudgetCalculationService::class);

        $this->bankAccount = BankAccount::query()->create([
            BankAccount::user_id => $this->user->id,
            BankAccount::name => 'Testkonto',
            BankAccount::type => BankAccountTypeEnum::GIRO->name,
        ]);

        $this->parentCategory = TransactionCategory::query()->create([
            TransactionCategory::user_id => $this->user->id,
            TransactionCategory::name => 'Lebenshaltung',
        ]);

        $this->childCategory = TransactionCategory::query()->create([
            TransactionCategory::user_id => $this->user->id,
            TransactionCategory::name => 'Lebensmittel',
            TransactionCategory::parent_id => $this->parentCategory->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }
}
