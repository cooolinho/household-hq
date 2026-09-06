<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\Contracts\FinancialBudgetTransactionCategory;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\EnergyTracker\MeasurementDeviceContractPrice;
use App\Models\EnergyTracker\ReadingEntry;
use App\Models\Financial\BankAccount;
use App\Models\Financial\Budget;
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostCategory;
use App\Models\Financial\Insurance;
use App\Models\Financial\InsuranceCategory;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Financial\TransactionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Counts driven by the curated rule catalog in
     * {@see \Database\Seeders\Financial\CategoryRules\CategoryRuleProvider}.
     * They change whenever keywords are tuned, so they are only checked for
     * idempotency instead of an exact value.
     *
     * @var list<string>
     */
    private const array CATALOG_DRIVEN_COUNTS = [
        'transaction_category_rules',
        'transaction_category_criteria',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 9, 5)->startOfDay());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_does_not_create_duplicate_demo_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $firstRunCounts = $this->demoCounts();

        self::assertSame([
            'users' => 2,
            'contacts' => 3,
            'tags' => 10,
            'bank_accounts' => 1,
            'csv_import_profiles' => 1,
            'transactions' => 164,
            'insurance_categories' => 21,
            'insurances' => 2,
            'fixed_cost_categories' => 50,
            'fixed_costs' => 9,
            'transaction_categories' => 47,
            'budgets' => 5,
            'budget_categories' => 5,
            'measurement_devices' => 2,
            'reading_entries' => 12,
            'contracts' => 3,
            'contract_prices' => 7,
        ], Arr::except($firstRunCounts, self::CATALOG_DRIVEN_COUNTS));
        self::assertSame(46, TransactionCategory::query()->whereNull(TransactionCategory::user_id)->count());
        self::assertGreaterThan(0, $firstRunCounts['transaction_category_rules']);
        self::assertGreaterThanOrEqual(
            $firstRunCounts['transaction_category_rules'],
            $firstRunCounts['transaction_category_criteria'],
        );
        self::assertSame(
            $firstRunCounts['transaction_category_rules'],
            TransactionCategoryRule::query()->whereNull(TransactionCategoryRule::user_id)->count(),
        );
        self::assertSame(
            TransactionSeeder::UNCATEGORIZED_TRANSACTION_COUNT,
            Transaction::query()
                ->where(Transaction::purpose, 'like', TransactionSeeder::UNCATEGORIZED_TRANSACTION_PURPOSE_PREFIX . '%')
                ->count(),
        );

        $fixedCosts = FixedCost::query()->get();
        foreach ($fixedCosts as $fixedCost) {
            $demoTransactions = $fixedCost->transactions()
                ->where(Transaction::description, TransactionSeeder::FIXED_COST_TRANSACTION_DESCRIPTION)
                ->get();

            self::assertNotEmpty($demoTransactions, sprintf(
                'Expected linked demo transactions for fixed cost "%s".',
                $fixedCost->name,
            ));
            self::assertSame(
                $demoTransactions->count(),
                $demoTransactions->filter(
                    fn(Transaction $transaction): bool => $transaction->transactionCategories()->doesntExist(),
                )->count(),
                sprintf('Fixed cost demo transactions must remain uncategorized for "%s".', $fixedCost->name),
            );
            self::assertTrue($demoTransactions->every(
                fn(Transaction $transaction): bool => $transaction->date->between(
                    now()->subYear()->startOfDay(),
                    now()->endOfDay(),
                ),
            ));
        }

        $customFixedCost = $fixedCosts->firstWhere(FixedCost::name, 'Wartungsvertrag');
        self::assertNotNull($customFixedCost);
        $customDates = $customFixedCost->transactions()
            ->where(Transaction::description, TransactionSeeder::FIXED_COST_TRANSACTION_DESCRIPTION)
            ->orderBy(Transaction::date)
            ->get()
            ->pluck(Transaction::date);

        foreach ($customDates->skip(1)->values() as $index => $date) {
            self::assertSame(
                4.0,
                $customDates[$index]->diffInMonths($date),
            );
        }

        $subcategories = TransactionCategory::query()
            ->whereNull(TransactionCategory::user_id)
            ->whereNotNull(TransactionCategory::parent_id)
            ->get();

        foreach ($subcategories as $subcategory) {
            self::assertSame(
                1,
                $subcategory->transactions()
                    ->where(Transaction::description, 'Demo-Kategorie')
                    ->count(),
                sprintf('Expected a category demo transaction for "%s".', $subcategory->name),
            );
        }

        $this->seed(DatabaseSeeder::class);

        self::assertSame($firstRunCounts, $this->demoCounts());
        self::assertSame(
            Transaction::query()->count(),
            Transaction::query()->distinct(Transaction::hash)->count(Transaction::hash),
        );
    }

    public function test_it_updates_demo_transaction_dates_when_the_seed_day_changes(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 5)->startOfDay());

        try {
            $this->seed(DatabaseSeeder::class);

            $firstTransactionCount = Transaction::query()->count();
            $firstSpotifyDate = Transaction::query()
                ->where(Transaction::payer, 'Spotify AB')
                ->where(Transaction::purpose, 'Spotify Premium Abo')
                ->firstOrFail()
                ->date
                ->toDateString();

            Carbon::setTestNow(Carbon::create(2026, 9, 6)->startOfDay());
            $this->seed(DatabaseSeeder::class);

            self::assertLessThanOrEqual($firstTransactionCount, Transaction::query()->count());
            self::assertNotSame(
                $firstSpotifyDate,
                Transaction::query()
                    ->where(Transaction::payer, 'Spotify AB')
                    ->where(Transaction::purpose, 'Spotify Premium Abo')
                    ->firstOrFail()
                    ->date
                    ->toDateString(),
            );

            foreach (Transaction::query()->get() as $transaction) {
                self::assertLessThanOrEqual(
                    '2026-09-06',
                    $transaction->date->toDateString(),
                );
                self::assertSame(
                    $transaction->date->toDateString(),
                    $transaction->value_date->toDateString(),
                );
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array<string, int>
     */
    private function demoCounts(): array
    {
        return [
            'users' => User::query()->count(),
            'contacts' => ContactPerson::query()->count(),
            'tags' => Tag::query()->count(),
            'bank_accounts' => BankAccount::query()->count(),
            'csv_import_profiles' => CSVImportProfile::query()->count(),
            'transactions' => Transaction::query()->count(),
            'insurance_categories' => InsuranceCategory::query()->count(),
            'insurances' => Insurance::query()->count(),
            'fixed_cost_categories' => FixedCostCategory::query()->count(),
            'fixed_costs' => FixedCost::query()->count(),
            'transaction_categories' => TransactionCategory::query()->count(),
            'budgets' => Budget::query()->count(),
            'budget_categories' => DB::table(FinancialBudgetTransactionCategory::PIVOT_TABLE)->count(),
            'transaction_category_rules' => TransactionCategoryRule::query()->count(),
            'transaction_category_criteria' => TransactionCategoryCriterion::query()->count(),
            'measurement_devices' => MeasurementDevice::query()->count(),
            'reading_entries' => ReadingEntry::query()->count(),
            'contracts' => MeasurementDeviceContract::query()->count(),
            'contract_prices' => MeasurementDeviceContractPrice::query()->count(),
        ];
    }
}
