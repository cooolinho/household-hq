<?php

namespace Tests\Feature;

use App\Models\Contracts\FinancialTransactionCategory;
use App\Models\Financial\BankAccount;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\User;
use Database\Seeders\Financial\TransactionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetTransactionCategoriesCommandTest extends TestCase
{
    use RefreshDatabase;

    private const string CHOICE_PROMPT = 'Welche Kategorien sollen gelöscht werden?';

    private const string CONFIRM_PROMPT = 'Möchtest du die ausgewählten Kategorien wirklich löschen und den System-Seeder ausführen?';

    private const string SYSTEM_ONLY_OPTION = 'Nur Systemkategorien löschen (Benutzerkategorien behalten)';

    private const string ALL_OPTION = 'Alle Kategorien löschen (inklusive Benutzerkategorien)';

    private const string ABORT_OPTION = 'Abbrechen';

    public function test_it_supports_aborting_before_deleting_categories(): void
    {
        $this->seed(TransactionCategorySeeder::class);
        $categoryCount = TransactionCategory::query()->count();

        $this->artisan('app:reset-transaction-categories')
            ->expectsChoice(self::CHOICE_PROMPT, self::ABORT_OPTION, $this->choices())
            ->expectsOutput('Abgebrochen.')
            ->assertSuccessful();

        self::assertSame($categoryCount, TransactionCategory::query()->count());
    }

    /**
     * @return list<string>
     */
    private function choices(): array
    {
        return [
            self::SYSTEM_ONLY_OPTION,
            self::ALL_OPTION,
            self::ABORT_OPTION,
        ];
    }

    public function test_it_replaces_system_categories_and_keeps_user_categories(): void
    {
        $this->seed(TransactionCategorySeeder::class);

        $user = User::factory()->create();
        $legacySystemCategory = $this->createCategory('Alte Systemkategorie', null);
        $userCategory = $this->createCategory('Eigene Kategorie', $user->id, $legacySystemCategory->id);
        $rule = TransactionCategoryRule::query()->create([
            TransactionCategoryRule::transaction_category_id => $legacySystemCategory->id,
            TransactionCategoryRule::user_id => null,
            TransactionCategoryRule::operator => TransactionCategoryRule::OPERATOR_OR,
            TransactionCategoryRule::active => true,
        ]);
        TransactionCategoryCriterion::query()->create([
            TransactionCategoryCriterion::transaction_category_rule_id => $rule->id,
            TransactionCategoryCriterion::field => TransactionCategoryCriterion::FIELD_PURPOSE,
            TransactionCategoryCriterion::operator => TransactionCategoryCriterion::OP_CONTAINS,
            TransactionCategoryCriterion::value => 'alte systemkategorie',
            TransactionCategoryCriterion::case_sensitive => false,
        ]);
        $transaction = $this->createTransaction($user);
        $transaction->transactionCategories()->attach($legacySystemCategory);

        $this->artisan('app:reset-transaction-categories')
            ->expectsChoice(self::CHOICE_PROMPT, self::SYSTEM_ONLY_OPTION, $this->choices())
            ->expectsConfirmation(self::CONFIRM_PROMPT, 'yes')
            ->assertSuccessful();

        $this->assertDatabaseMissing(TransactionCategory::TABLE, [
            TransactionCategory::id => $legacySystemCategory->id,
        ]);
        $this->assertDatabaseHas(TransactionCategory::TABLE, [
            TransactionCategory::id => $userCategory->id,
            TransactionCategory::user_id => $user->id,
            TransactionCategory::parent_id => null,
        ]);
        $this->assertDatabaseHas(TransactionCategory::TABLE, [
            TransactionCategory::user_id => null,
            TransactionCategory::name => 'Finanzen & Versicherungen',
        ]);
        $this->assertDatabaseMissing(TransactionCategoryRule::TABLE, [
            TransactionCategoryRule::id => $rule->id,
        ]);
        $this->assertDatabaseHas(Transaction::TABLE, [
            Transaction::id => $transaction->id,
        ]);
        $this->assertDatabaseMissing(FinancialTransactionCategory::PIVOT_TABLE, [
            FinancialTransactionCategory::TRANSACTION_ID => $transaction->id,
            FinancialTransactionCategory::CATEGORY_ID => $legacySystemCategory->id,
        ]);
    }

    private function createCategory(string $name, ?int $userId, ?int $parentId = null): TransactionCategory
    {
        return TransactionCategory::query()->create([
            TransactionCategory::user_id => $userId,
            TransactionCategory::name => $name,
            TransactionCategory::parent_id => $parentId,
            TransactionCategory::active => true,
        ]);
    }

    private function createTransaction(User $user): Transaction
    {
        $bankAccount = BankAccount::query()->create([
            BankAccount::name => 'Testkonto',
            BankAccount::user_id => $user->id,
        ]);

        return Transaction::factory()
            ->forUser($user->id)
            ->forBankAccount($bankAccount->id)
            ->create();
    }

    public function test_it_can_delete_user_categories_and_reseed_only_system_categories(): void
    {
        $this->seed(TransactionCategorySeeder::class);

        $user = User::factory()->create();
        $userCategory = $this->createCategory('Zu löschende eigene Kategorie', $user->id);
        $transaction = $this->createTransaction($user);
        $transaction->transactionCategories()->attach($userCategory);

        $this->artisan('app:reset-transaction-categories')
            ->expectsChoice(self::CHOICE_PROMPT, self::ALL_OPTION, $this->choices())
            ->expectsConfirmation(self::CONFIRM_PROMPT, 'yes')
            ->assertSuccessful();

        $this->assertDatabaseMissing(TransactionCategory::TABLE, [
            TransactionCategory::id => $userCategory->id,
        ]);
        $this->assertDatabaseHas(TransactionCategory::TABLE, [
            TransactionCategory::user_id => null,
            TransactionCategory::name => 'Finanzen & Versicherungen',
        ]);
        $this->assertDatabaseHas(Transaction::TABLE, [
            Transaction::id => $transaction->id,
        ]);
        $this->assertDatabaseMissing(FinancialTransactionCategory::PIVOT_TABLE, [
            FinancialTransactionCategory::TRANSACTION_ID => $transaction->id,
            FinancialTransactionCategory::CATEGORY_ID => $userCategory->id,
        ]);
    }
}
