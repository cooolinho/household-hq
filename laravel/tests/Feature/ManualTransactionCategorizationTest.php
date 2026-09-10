<?php

namespace Tests\Feature;

use App\Filament\App\Resources\Financial\TransactionCategories\Pages\ListTransactionCategories;
use App\Jobs\Financial\CategorizeUncategorizedTransactionsJob;
use App\Jobs\Financial\RecategorizeAllTransactionsJob;
use App\Jobs\Financial\RecategorizeCategorizedTransactionsJob;
use App\Models\Financial\BankAccount;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\User;
use App\Services\TransactionCategorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ManualTransactionCategorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_recategorizes_existing_transactions_without_duplicate_categories(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);
        $existingCategory = $this->createCategory('Bestehend');
        $newCategory = $this->createCategory('Neu');
        $this->createPayerRule($existingCategory, 'acme');
        $this->createPayerRule($newCategory, 'acme');

        $categorized = $this->createTransaction($user, $bankAccount, 'ACME GmbH', 'Bestehende Zuordnung');
        $uncategorized = $this->createTransaction($user, $bankAccount, 'ACME GmbH', 'Keine Zuordnung');
        $otherUser = User::factory()->create();
        $otherBankAccount = $this->createBankAccount($otherUser);
        $otherTransaction = $this->createTransaction($otherUser, $otherBankAccount, 'ACME GmbH', 'Anderer Benutzer');

        $categorized->transactionCategories()->attach($existingCategory);

        $service = app(TransactionCategorizationService::class);
        $results = $service->recategorizeCategorized($user->id);

        self::assertSame(['processed' => 1, 'categorized' => 1, 'skipped' => 0, 'removed' => 0], $results);
        $this->assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $categorized->id,
            'transaction_category_id' => $existingCategory->id,
        ]);
        $this->assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $categorized->id,
            'transaction_category_id' => $newCategory->id,
        ]);
        $this->assertSame(
            2,
            DB::table('financial_transaction_transaction_category')
                ->where('transaction_id', $categorized->id)
                ->count(),
        );
        $this->assertDatabaseMissing('financial_transaction_transaction_category', [
            'transaction_id' => $uncategorized->id,
        ]);
        $this->assertDatabaseMissing('financial_transaction_transaction_category', [
            'transaction_id' => $otherTransaction->id,
        ]);

        $service->recategorizeCategorized($user->id);

        self::assertSame(
            2,
            DB::table('financial_transaction_transaction_category')
                ->where('transaction_id', $categorized->id)
                ->count(),
        );
    }

    private function createBankAccount(User $user): BankAccount
    {
        return BankAccount::query()->create([
            BankAccount::name => 'Testkonto',
            BankAccount::user_id => $user->id,
        ]);
    }

    private function createCategory(string $name): TransactionCategory
    {
        return TransactionCategory::query()->create([
            TransactionCategory::user_id => null,
            TransactionCategory::name => $name,
            TransactionCategory::parent_id => null,
            TransactionCategory::active => true,
        ]);
    }

    private function createPayerRule(TransactionCategory $category, string $payer): void
    {
        $rule = TransactionCategoryRule::query()->create([
            TransactionCategoryRule::transaction_category_id => $category->id,
            TransactionCategoryRule::user_id => null,
            TransactionCategoryRule::operator => TransactionCategoryRule::OPERATOR_OR,
            TransactionCategoryRule::active => true,
        ]);

        TransactionCategoryCriterion::query()->create([
            TransactionCategoryCriterion::transaction_category_rule_id => $rule->id,
            TransactionCategoryCriterion::field => TransactionCategoryCriterion::FIELD_PAYER,
            TransactionCategoryCriterion::operator => TransactionCategoryCriterion::OP_CONTAINS,
            TransactionCategoryCriterion::value => $payer,
            TransactionCategoryCriterion::case_sensitive => false,
        ]);
    }

    private function createTransaction(User $user, BankAccount $bankAccount, string $payer, string $purpose): Transaction
    {
        $data = [
            Transaction::date => '2026-09-01',
            Transaction::value_date => '2026-09-01',
            Transaction::payer => $payer,
            Transaction::description => $purpose,
            Transaction::purpose => $purpose,
            Transaction::amount => -9.99,
            Transaction::balance => 500.00,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
            Transaction::bank_account_id => $bankAccount->id,
            Transaction::user_id => $user->id,
        ];
        $data[Transaction::hash] = Transaction::createHash($data);

        return Transaction::query()->create($data);
    }

    public function test_it_categorizes_only_uncategorized_transactions_for_the_user(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);
        $category = $this->createCategory('Passend');
        $this->createPayerRule($category, 'acme');

        $alreadyCategorized = $this->createTransaction($user, $bankAccount, 'ACME GmbH', 'Bereits kategorisiert');
        $uncategorized = $this->createTransaction($user, $bankAccount, 'ACME GmbH', 'Noch nicht kategorisiert');
        $alreadyCategorized->transactionCategories()->attach($category);

        $otherUser = User::factory()->create();
        $otherBankAccount = $this->createBankAccount($otherUser);
        $otherTransaction = $this->createTransaction($otherUser, $otherBankAccount, 'ACME GmbH', 'Anderer Benutzer');

        $results = app(TransactionCategorizationService::class)->categorizeUncategorized($user->id);

        self::assertSame(['processed' => 1, 'categorized' => 1, 'skipped' => 0, 'removed' => 0], $results);
        $this->assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $alreadyCategorized->id,
            'transaction_category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $uncategorized->id,
            'transaction_category_id' => $category->id,
        ]);
        $this->assertDatabaseMissing('financial_transaction_transaction_category', [
            'transaction_id' => $otherTransaction->id,
        ]);
    }

    public function test_it_removes_all_categories_before_recategorizing(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);
        $obsoleteCategory = $this->createCategory('Veraltet');
        $matchingCategory = $this->createCategory('Passend');
        $this->createPayerRule($matchingCategory, 'acme');

        $matchingTransaction = $this->createTransaction($user, $bankAccount, 'ACME GmbH', 'Passend');
        $unmatchedTransaction = $this->createTransaction($user, $bankAccount, 'Andere GmbH', 'Ohne Treffer');
        $matchingTransaction->transactionCategories()->attach($obsoleteCategory);
        $unmatchedTransaction->transactionCategories()->attach($obsoleteCategory);

        $otherUser = User::factory()->create();
        $otherBankAccount = $this->createBankAccount($otherUser);
        $otherTransaction = $this->createTransaction($otherUser, $otherBankAccount, 'ACME GmbH', 'Anderer Benutzer');
        $otherTransaction->transactionCategories()->attach($obsoleteCategory);

        $results = app(TransactionCategorizationService::class)->recategorizeAll($user->id);

        self::assertSame(['processed' => 2, 'categorized' => 1, 'skipped' => 1, 'removed' => 0], $results);
        $this->assertDatabaseMissing('financial_transaction_transaction_category', [
            'transaction_id' => $matchingTransaction->id,
            'transaction_category_id' => $obsoleteCategory->id,
        ]);
        $this->assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $matchingTransaction->id,
            'transaction_category_id' => $matchingCategory->id,
        ]);
        $this->assertDatabaseMissing('financial_transaction_transaction_category', [
            'transaction_id' => $unmatchedTransaction->id,
        ]);
        $this->assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $otherTransaction->id,
            'transaction_category_id' => $obsoleteCategory->id,
        ]);
    }

    public function test_header_action_dispatches_the_selected_job_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        Queue::fake();

        $component = Livewire::actingAs($user)->test(ListTransactionCategories::class);

        $component
            ->assertActionExists('categorizeTransactions')
            ->callAction('categorizeTransactions', ['mode' => 'recategorize_categorized']);

        Queue::assertPushed(
            RecategorizeCategorizedTransactionsJob::class,
            fn(RecategorizeCategorizedTransactionsJob $job): bool => $job->userId === $user->id,
        );

        Livewire::actingAs($user)
            ->test(ListTransactionCategories::class)
            ->callAction('categorizeTransactions', ['mode' => 'categorize_uncategorized']);
        Queue::assertPushed(
            CategorizeUncategorizedTransactionsJob::class,
            fn(CategorizeUncategorizedTransactionsJob $job): bool => $job->userId === $user->id,
        );

        Livewire::actingAs($user)
            ->test(ListTransactionCategories::class)
            ->callAction('categorizeTransactions', ['mode' => 'recategorize_all']);
        Queue::assertPushed(
            RecategorizeAllTransactionsJob::class,
            fn(RecategorizeAllTransactionsJob $job): bool => $job->userId === $user->id,
        );
    }

    public function test_job_sends_completion_notification_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        (new CategorizeUncategorizedTransactionsJob($user->id))
            ->handle(app(TransactionCategorizationService::class));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $otherUser->id,
        ]);
        self::assertSame(1, DB::table('notifications')->count());
    }

    public function test_failed_job_sends_error_notification_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        (new CategorizeUncategorizedTransactionsJob($user->id))
            ->failed(new RuntimeException('Testfehler'));

        $notification = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->first();

        self::assertNotNull($notification);
        self::assertStringContainsString(
            'fehlgeschlagen',
            (string)json_decode($notification->data, true, flags: JSON_THROW_ON_ERROR)['title'],
        );
    }
}
