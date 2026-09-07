<?php

namespace Tests\Feature;

use App\Filament\App\Resources\Financial\Budgets\Pages\CreateBudget;
use App\Filament\App\Resources\Financial\Budgets\Pages\EditBudget;
use App\Filament\App\Resources\Financial\Budgets\Pages\ListBudgets;
use App\Filament\App\Resources\Financial\Budgets\Pages\ViewBudget;
use App\Filament\App\Resources\Financial\Budgets\RelationManagers\TransactionsRelationManager;
use App\Models\Enums\BankAccountTypeEnum;
use App\Models\Enums\BudgetIconEnum;
use App\Models\Enums\BudgetPeriodEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\Budget;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\User;
use App\Services\Budget\BudgetCalculation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetResourceTest extends TestCase
{
    use RefreshDatabase;

    private const string NOW = '2026-09-10 12:00:00';

    private User $user;

    private BankAccount $bankAccount;

    private TransactionCategory $category;

    public function test_user_can_create_a_budget_and_the_owner_is_set_automatically(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreateBudget::class)
            ->fillForm([
                Budget::name => 'Lebensmittel & Getränke',
                Budget::icon => BudgetIconEnum::SHOPPING_CART->name,
                Budget::amount => 600,
                Budget::currency => 'EUR',
                Budget::period => BudgetPeriodEnum::MONTHLY->name,
                Budget::belongs_to_many_transaction_categories => [$this->category->id],
                Budget::include_subcategories => true,
                Budget::warning_threshold => 75,
                Budget::critical_threshold => 100,
                Budget::send_notification => true,
                Budget::send_mail => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $budget = Budget::query()->firstOrFail();

        self::assertSame($this->user->id, $budget->user_id);
        self::assertSame(600.0, $budget->amount);
        self::assertSame(75, $budget->warning_threshold);
        self::assertSame(BudgetIconEnum::SHOPPING_CART, $budget->icon);
        self::assertSame(BudgetPeriodEnum::MONTHLY, $budget->period);
        self::assertSame([$this->category->id], $budget->transactionCategories->pluck(TransactionCategory::id)->all());
    }

    public function test_user_can_edit_a_budget(): void
    {
        $budget = $this->createBudget(200.0);

        Livewire::actingAs($this->user)
            ->test(EditBudget::class, ['record' => $budget->getRouteKey()])
            ->fillForm([
                Budget::name => 'Neuer Name',
                Budget::amount => 350,
                Budget::warning_threshold => 60,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $budget->refresh();

        self::assertSame('Neuer Name', $budget->name);
        self::assertSame(350.0, $budget->amount);
        self::assertSame(60, $budget->warning_threshold);
    }

    private function createBudget(float $amount, string $name = 'Lebensmittelbudget'): Budget
    {
        $budget = Budget::query()->create([
            Budget::user_id => $this->user->id,
            Budget::name => $name,
            Budget::amount => $amount,
        ]);

        $budget->transactionCategories()->sync([$this->category->id]);

        return $budget;
    }

    public function test_overview_only_lists_own_active_budgets(): void
    {
        $own = $this->createBudget(200.0);

        $inactive = $this->createBudget(200.0, 'Inaktiv');
        $inactive->forceFill([Budget::active => false])->save();

        $otherUser = User::factory()->create();
        Budget::query()->create([
            Budget::user_id => $otherUser->id,
            Budget::name => 'Fremdbudget',
            Budget::amount => 200.0,
        ]);

        $calculations = Livewire::actingAs($this->user)
            ->test(ListBudgets::class)
            ->instance()
            ->budgetCalculations();

        self::assertSame(
            [$own->id],
            array_map(static fn(BudgetCalculation $calculation): int => $calculation->budget->id, $calculations),
        );
    }

    public function test_overview_page_renders_the_card_grid(): void
    {
        $this->createBudget(200.0);
        $this->createTransaction(-180.0, '2026-09-02');

        Livewire::actingAs($this->user)
            ->test(ListBudgets::class)
            ->assertOk()
            ->assertSee('Lebensmittelbudget')
            ->assertSee('September 2026');
    }

    private function createTransaction(float $amount, string $date): Transaction
    {
        $transaction = Transaction::factory()
            ->forUser((int)$this->user->id)
            ->forBankAccount((int)$this->bankAccount->id)
            ->create([
                Transaction::date => $date,
                Transaction::value_date => $date,
                Transaction::amount => $amount,
                Transaction::amount_currency => 'EUR',
            ]);

        $transaction->transactionCategories()->sync([$this->category->id]);

        return $transaction;
    }

    public function test_detail_page_renders_the_infolist(): void
    {
        $budget = $this->createBudget(200.0);
        $this->createTransaction(-50.0, '2026-09-02');

        Livewire::actingAs($this->user)
            ->test(ViewBudget::class, ['record' => $budget->getRouteKey()])
            ->assertOk()
            ->assertSee('Lebensmittelbudget');
    }

    public function test_relation_manager_only_lists_transactions_of_the_current_period(): void
    {
        $budget = $this->createBudget(200.0);

        $inPeriod = $this->createTransaction(-50.0, '2026-09-02');
        $beforePeriod = $this->createTransaction(-70.0, '2026-08-20');
        $unrelated = Transaction::factory()
            ->forUser((int)$this->user->id)
            ->forBankAccount((int)$this->bankAccount->id)
            ->create([
                Transaction::date => '2026-09-03',
                Transaction::amount => -90.0,
                Transaction::amount_currency => 'EUR',
            ]);

        $component = Livewire::actingAs($this->user)->test(TransactionsRelationManager::class, [
            'ownerRecord' => $budget,
            'pageClass' => ViewBudget::class,
        ]);

        $component
            ->assertCanSeeTableRecords([$inPeriod])
            ->assertCanNotSeeTableRecords([$beforePeriod, $unrelated]);

        $component
            ->filterTable('all_periods')
            ->assertCanSeeTableRecords([$inPeriod, $beforePeriod])
            ->assertCanNotSeeTableRecords([$unrelated]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::NOW));
        CarbonImmutable::setTestNow(CarbonImmutable::parse(self::NOW));

        $this->user = User::factory()->create();

        $this->bankAccount = BankAccount::query()->create([
            BankAccount::user_id => $this->user->id,
            BankAccount::name => 'Testkonto',
            BankAccount::type => BankAccountTypeEnum::GIRO->name,
        ]);

        $this->category = TransactionCategory::query()->create([
            TransactionCategory::user_id => $this->user->id,
            TransactionCategory::name => 'Lebensmittel',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }
}
