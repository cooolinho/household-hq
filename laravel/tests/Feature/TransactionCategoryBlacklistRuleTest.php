<?php

namespace Tests\Feature;

use App\Filament\App\Resources\Financial\TransactionCategories\Pages\EditTransactionCategory;
use App\Models\Financial\BankAccount;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\User;
use App\Services\TransactionCategorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionCategoryBlacklistRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_blacklist_rule_overrides_a_matching_system_rule(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);

        $housing = $this->createCategory(null, 'Wohnen & Wohnnebenkosten');
        $mobility = $this->createCategory(null, 'Verkehrsmittel');

        // Systemregel: "Wasser" im Auftraggeber/Verwendungszweck -> Wohnen
        $this->createRule($housing, null, TransactionCategoryRule::TYPE_INCLUDE, [
            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'wasser'],
        ]);

        // Benutzer-Blacklist: "Verkehr und Wasser" darf nicht als Wohnen erkannt werden
        $this->createRule($housing, $user->id, TransactionCategoryRule::TYPE_EXCLUDE, [
            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'Verkehr und Wasser'],
        ]);

        // Benutzer-Zusatzregel: Deutschland-Ticket-Anbieter -> Verkehrsmittel
        $this->createRule($mobility, $user->id, TransactionCategoryRule::TYPE_INCLUDE, [
            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'Verkehr und Wasser'],
        ]);

        $ticketTransaction = $this->createTransaction($user, $bankAccount, 'Verkehr und Wasser GmbH', 'Deutschland-Ticket');
        $waterTransaction = $this->createTransaction($user, $bankAccount, 'Stadtwerke Wasser', 'Abschlag Wasser Q3');

        app(TransactionCategorizationService::class)->categorizeUncategorized($user->id);

        $this->assertDatabaseMissing('financial_transaction_transaction_category', [
            'transaction_id' => $ticketTransaction->id,
            'transaction_category_id' => $housing->id,
        ]);
        $this->assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $ticketTransaction->id,
            'transaction_category_id' => $mobility->id,
        ]);
        $this->assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $waterTransaction->id,
            'transaction_category_id' => $housing->id,
        ]);
    }

    private function createBankAccount(User $user): BankAccount
    {
        return BankAccount::query()->create([
            BankAccount::name => 'Testkonto',
            BankAccount::user_id => $user->id,
        ]);
    }

    private function createCategory(?int $userId, string $name): TransactionCategory
    {
        return TransactionCategory::query()->create([
            TransactionCategory::user_id => $userId,
            TransactionCategory::name => $name,
            TransactionCategory::parent_id => null,
            TransactionCategory::active => true,
        ]);
    }

    /**
     * @param array<int, array{0: string, 1: string, 2: string}> $criteria field/operator/value triples
     */
    private function createRule(TransactionCategory $category, ?int $userId, string $type, array $criteria): TransactionCategoryRule
    {
        $rule = TransactionCategoryRule::query()->create([
            TransactionCategoryRule::transaction_category_id => $category->id,
            TransactionCategoryRule::user_id => $userId,
            TransactionCategoryRule::type => $type,
            TransactionCategoryRule::operator => TransactionCategoryRule::OPERATOR_OR,
            TransactionCategoryRule::active => true,
        ]);

        foreach ($criteria as [$field, $operator, $value]) {
            TransactionCategoryCriterion::query()->create([
                TransactionCategoryCriterion::transaction_category_rule_id => $rule->id,
                TransactionCategoryCriterion::field => $field,
                TransactionCategoryCriterion::operator => $operator,
                TransactionCategoryCriterion::value => $value,
                TransactionCategoryCriterion::case_sensitive => false,
            ]);
        }

        return $rule;
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

    public function test_a_category_with_only_blacklist_rules_never_matches(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);

        $category = $this->createCategory($user->id, 'Nur Blacklist');
        $this->createRule($category, $user->id, TransactionCategoryRule::TYPE_EXCLUDE, [
            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'acme'],
        ]);

        $transaction = $this->createTransaction($user, $bankAccount, 'ACME GmbH', 'Test');

        app(TransactionCategorizationService::class)->categorizeUncategorized($user->id);

        $this->assertDatabaseMissing('financial_transaction_transaction_category', [
            'transaction_id' => $transaction->id,
            'transaction_category_id' => $category->id,
        ]);
    }

    public function test_blacklist_wins_against_an_own_matching_rule_of_the_same_category(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);

        $category = $this->createCategory($user->id, 'Veto-Test');
        $this->createRule($category, $user->id, TransactionCategoryRule::TYPE_INCLUDE, [
            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'acme'],
        ]);
        $this->createRule($category, $user->id, TransactionCategoryRule::TYPE_EXCLUDE, [
            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'ausnahme'],
        ]);

        $transaction = $this->createTransaction($user, $bankAccount, 'ACME GmbH', 'Ausnahme Buchung');

        app(TransactionCategorizationService::class)->categorizeUncategorized($user->id);

        $this->assertDatabaseMissing('financial_transaction_transaction_category', [
            'transaction_id' => $transaction->id,
            'transaction_category_id' => $category->id,
        ]);
    }

    public function test_an_inactive_blacklist_rule_is_ignored(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);

        $category = $this->createCategory($user->id, 'Inaktive Blacklist');
        $this->createRule($category, $user->id, TransactionCategoryRule::TYPE_INCLUDE, [
            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'acme'],
        ]);

        $blacklistRule = TransactionCategoryRule::query()->create([
            TransactionCategoryRule::transaction_category_id => $category->id,
            TransactionCategoryRule::user_id => $user->id,
            TransactionCategoryRule::type => TransactionCategoryRule::TYPE_EXCLUDE,
            TransactionCategoryRule::operator => TransactionCategoryRule::OPERATOR_OR,
            TransactionCategoryRule::active => false,
        ]);
        TransactionCategoryCriterion::query()->create([
            TransactionCategoryCriterion::transaction_category_rule_id => $blacklistRule->id,
            TransactionCategoryCriterion::field => TransactionCategoryCriterion::FIELD_PAYER,
            TransactionCategoryCriterion::operator => TransactionCategoryCriterion::OP_CONTAINS,
            TransactionCategoryCriterion::value => 'acme',
            TransactionCategoryCriterion::case_sensitive => false,
        ]);

        $transaction = $this->createTransaction($user, $bankAccount, 'ACME GmbH', 'Test');

        app(TransactionCategorizationService::class)->categorizeUncategorized($user->id);

        $this->assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $transaction->id,
            'transaction_category_id' => $category->id,
        ]);
    }

    public function test_a_blacklist_rule_only_affects_its_own_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $bankA = $this->createBankAccount($userA);
        $bankB = $this->createBankAccount($userB);

        $category = $this->createCategory(null, 'Global mit Blacklist');
        $this->createRule($category, null, TransactionCategoryRule::TYPE_INCLUDE, [
            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'wasser'],
        ]);
        $this->createRule($category, $userA->id, TransactionCategoryRule::TYPE_EXCLUDE, [
            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'Verkehr und Wasser'],
        ]);

        $transactionA = $this->createTransaction($userA, $bankA, 'Verkehr und Wasser GmbH', 'Deutschland-Ticket');
        $transactionB = $this->createTransaction($userB, $bankB, 'Verkehr und Wasser GmbH', 'Deutschland-Ticket');

        $service = app(TransactionCategorizationService::class);
        $service->categorizeUncategorized($userA->id);
        $service->categorizeUncategorized($userB->id);

        $this->assertDatabaseMissing('financial_transaction_transaction_category', [
            'transaction_id' => $transactionA->id,
            'transaction_category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $transactionB->id,
            'transaction_category_id' => $category->id,
        ]);
    }

    public function test_recategorizing_detaches_a_category_once_a_blacklist_rule_matches(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);

        $category = $this->createCategory($user->id, 'Bestand');
        $this->createRule($category, $user->id, TransactionCategoryRule::TYPE_INCLUDE, [
            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'acme'],
        ]);

        $transaction = $this->createTransaction($user, $bankAccount, 'ACME GmbH', 'Test');
        // Manuell gesetzte Zuordnung, ohne dass jemals eine Regel gelaufen ist.
        $transaction->transactionCategories()->attach($category);

        // Nachträglich eine Blacklist-Regel ergänzen, die auf dieselbe Transaktion passt.
        $this->createRule($category, $user->id, TransactionCategoryRule::TYPE_EXCLUDE, [
            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'test'],
        ]);

        $results = app(TransactionCategorizationService::class)->recategorizeCategorized($user->id);

        self::assertSame(1, $results['removed']);
        $this->assertDatabaseMissing('financial_transaction_transaction_category', [
            'transaction_id' => $transaction->id,
            'transaction_category_id' => $category->id,
        ]);
    }

    public function test_blacklist_rule_works_on_an_own_non_global_category(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);

        $category = $this->createCategory($user->id, 'Eigene Kategorie');
        $this->createRule($category, $user->id, TransactionCategoryRule::TYPE_INCLUDE, [
            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'acme'],
        ]);
        $this->createRule($category, $user->id, TransactionCategoryRule::TYPE_EXCLUDE, [
            [TransactionCategoryCriterion::FIELD_PURPOSE, TransactionCategoryCriterion::OP_CONTAINS, 'ausnahme'],
        ]);

        $matching = $this->createTransaction($user, $bankAccount, 'ACME GmbH', 'Normale Buchung');
        $blacklisted = $this->createTransaction($user, $bankAccount, 'ACME GmbH', 'Ausnahme Buchung');

        app(TransactionCategorizationService::class)->categorizeUncategorized($user->id);

        $this->assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $matching->id,
            'transaction_category_id' => $category->id,
        ]);
        $this->assertDatabaseMissing('financial_transaction_transaction_category', [
            'transaction_id' => $blacklisted->id,
            'transaction_category_id' => $category->id,
        ]);
    }

    public function test_user_blacklist_rules_round_trip_through_the_edit_form(): void
    {
        $user = User::factory()->create();
        $category = $this->createCategory(null, 'Formular-Kategorie');
        $this->createRule($category, null, TransactionCategoryRule::TYPE_INCLUDE, [
            [TransactionCategoryCriterion::FIELD_PAYER, TransactionCategoryCriterion::OP_CONTAINS, 'wasser'],
        ]);

        $component = Livewire::actingAs($user)->test(EditTransactionCategory::class, [
            'record' => $category->getKey(),
        ]);

        $component
            ->set('data.user_blacklist_rules', [
                [
                    TransactionCategoryRule::operator => TransactionCategoryRule::OPERATOR_OR,
                    TransactionCategoryRule::active => true,
                    TransactionCategoryRule::has_many_criteria => [
                        [
                            TransactionCategoryCriterion::field => TransactionCategoryCriterion::FIELD_PAYER,
                            TransactionCategoryCriterion::operator => TransactionCategoryCriterion::OP_CONTAINS,
                            TransactionCategoryCriterion::value => 'Verkehr und Wasser',
                            TransactionCategoryCriterion::value_secondary => null,
                            TransactionCategoryCriterion::case_sensitive => false,
                        ],
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(TransactionCategoryRule::TABLE, [
            TransactionCategoryRule::transaction_category_id => $category->id,
            TransactionCategoryRule::user_id => $user->id,
            TransactionCategoryRule::type => TransactionCategoryRule::TYPE_EXCLUDE,
        ]);
        $this->assertDatabaseMissing(TransactionCategoryRule::TABLE, [
            TransactionCategoryRule::transaction_category_id => $category->id,
            TransactionCategoryRule::user_id => $user->id,
            TransactionCategoryRule::type => TransactionCategoryRule::TYPE_INCLUDE,
        ]);

        // Erneut öffnen: die Blacklist-Regel muss in ihren eigenen Topf zurückgeladen werden,
        // nicht in die "Eigene Zusatzregeln".
        $reopened = Livewire::actingAs($user)->test(EditTransactionCategory::class, [
            'record' => $category->getKey(),
        ]);

        self::assertCount(1, $reopened->get('data.user_blacklist_rules'));
        self::assertCount(0, $reopened->get('data.user_extension_rules'));
    }
}
