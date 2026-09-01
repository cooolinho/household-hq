<?php

namespace Tests\Feature;

use App\Models\Financial\BankAccount;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\Financial\TransactionCategoryRuleUserSetting;
use App\Models\User;
use App\Services\TransactionCategorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCategorizationGlobalCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_rules_can_be_disabled_per_user_and_extended_with_user_rules(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $bankA = BankAccount::query()->create([
            BankAccount::name => 'Giro A',
            BankAccount::user_id => $userA->id,
        ]);

        $bankB = BankAccount::query()->create([
            BankAccount::name => 'Giro B',
            BankAccount::user_id => $userB->id,
        ]);

        $parent = TransactionCategory::query()->create([
            TransactionCategory::user_id => null,
            TransactionCategory::name => 'Abonnements',
            TransactionCategory::parent_id => null,
            TransactionCategory::active => true,
        ]);

        $globalCategory = TransactionCategory::query()->create([
            TransactionCategory::user_id => null,
            TransactionCategory::name => 'Musik Streaming',
            TransactionCategory::parent_id => $parent->id,
            TransactionCategory::active => true,
        ]);

        $globalRule = TransactionCategoryRule::query()->create([
            TransactionCategoryRule::transaction_category_id => $globalCategory->id,
            TransactionCategoryRule::user_id => null,
            TransactionCategoryRule::operator => TransactionCategoryRule::OPERATOR_OR,
            TransactionCategoryRule::active => true,
        ]);

        TransactionCategoryCriterion::query()->create([
            TransactionCategoryCriterion::transaction_category_rule_id => $globalRule->id,
            TransactionCategoryCriterion::field => TransactionCategoryCriterion::FIELD_PAYER,
            TransactionCategoryCriterion::operator => TransactionCategoryCriterion::OP_CONTAINS,
            TransactionCategoryCriterion::value => 'spotify',
            TransactionCategoryCriterion::case_sensitive => false,
        ]);

        TransactionCategoryRuleUserSetting::query()->create([
            TransactionCategoryRuleUserSetting::user_id => $userA->id,
            TransactionCategoryRuleUserSetting::transaction_category_rule_id => $globalRule->id,
            TransactionCategoryRuleUserSetting::active => false,
        ]);

        $userExtensionRule = TransactionCategoryRule::query()->create([
            TransactionCategoryRule::transaction_category_id => $globalCategory->id,
            TransactionCategoryRule::user_id => $userA->id,
            TransactionCategoryRule::operator => TransactionCategoryRule::OPERATOR_OR,
            TransactionCategoryRule::active => true,
        ]);

        TransactionCategoryCriterion::query()->create([
            TransactionCategoryCriterion::transaction_category_rule_id => $userExtensionRule->id,
            TransactionCategoryCriterion::field => TransactionCategoryCriterion::FIELD_PAYER,
            TransactionCategoryCriterion::operator => TransactionCategoryCriterion::OP_CONTAINS,
            TransactionCategoryCriterion::value => 'custompayer',
            TransactionCategoryCriterion::case_sensitive => false,
        ]);

        $spotifyForUserA = $this->createTransaction($userA->id, $bankA->id, 'Spotify AB', 'Abo Spotify');
        $customForUserA = $this->createTransaction($userA->id, $bankA->id, 'CustomPayer Inc', 'Eigenes Mapping');
        $spotifyForUserB = $this->createTransaction($userB->id, $bankB->id, 'Spotify AB', 'Abo Spotify');

        $service = app(TransactionCategorizationService::class);
        $service->categorizeUncategorized($userA->id);
        $service->categorizeUncategorized($userB->id);

        self::assertDatabaseMissing('financial_transaction_transaction_category', [
            'transaction_id' => $spotifyForUserA->id,
            'transaction_category_id' => $globalCategory->id,
        ]);

        self::assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $customForUserA->id,
            'transaction_category_id' => $globalCategory->id,
        ]);

        self::assertDatabaseHas('financial_transaction_transaction_category', [
            'transaction_id' => $spotifyForUserB->id,
            'transaction_category_id' => $globalCategory->id,
        ]);
    }

    private function createTransaction(int $userId, int $bankAccountId, string $payer, string $purpose): Transaction
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
            Transaction::bank_account_id => $bankAccountId,
            Transaction::user_id => $userId,
        ];

        $data[Transaction::hash] = Transaction::createHash($data);

        return Transaction::query()->create($data);
    }
}
