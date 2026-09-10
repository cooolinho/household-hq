<?php

namespace Tests\Feature;

use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostMatchingRule;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionMatchingSuggestion;
use App\Models\User;
use App\Services\TransactionFixedCostMatchingService;
use App\Settings\FixedCostSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedCostTransactionCategoryMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_match_lifts_the_score_and_wins_against_an_otherwise_equal_candidate(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);
        $category = $this->createCategory('Miete');

        $fixedCostWithCategory = $this->createFixedCost($user, 'Miete', -1000.00);
        $fixedCostWithCategory->transactionCategories()->attach($category);
        $fixedCostWithoutCategory = $this->createFixedCost($user, 'Miete', -1000.00);

        $transaction = $this->createTransaction($user, $bankAccount, 'Vermieter GmbH', 'Miete September', -1000.00);
        $transaction->transactionCategories()->attach($category);

        $service = $this->service();

        // Betrag (60) + Text (30) + Datum (0) = 90 Basis-Score für beide Fixkosten.
        // Nur die verknüpfte Kategorie hebt den Score der einen Fixkosten-Position an.
        self::assertSame(91.5, $service->calculateScore($transaction, $fixedCostWithCategory));
        self::assertSame(90.0, $service->calculateScore($transaction, $fixedCostWithoutCategory));

        $outcome = $service->matchTransaction($transaction);

        self::assertSame('linked', $outcome);
        $transaction->refresh();
        self::assertSame($fixedCostWithCategory->id, $transaction->fixed_cost_id);
    }

    public function test_category_match_resolves_an_otherwise_ambiguous_tie(): void
    {
        $this->setSettings([
            'matching_threshold' => 20,
            'matching_category_weight' => 20,
        ]);

        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);
        $matchingCategory = $this->createCategory('Miete');
        $otherCategory = $this->createCategory('Versicherung');

        // Leerzeichen als Name -> Text-Score exakt 0, damit nur Betrag/Datum/Kategorie zählen.
        $fixedCostDirectMatch = $this->createFixedCost($user, ' ', -85.00, [
            FixedCost::next_booking_date => null,
        ]);
        $fixedCostDirectMatch->transactionCategories()->attach($matchingCategory);

        $fixedCostMismatch = $this->createFixedCost($user, ' ', -93.00, [
            FixedCost::next_booking_date => '2026-09-02',
        ]);
        $fixedCostMismatch->transactionCategories()->attach($otherCategory);

        $transaction = $this->createTransaction($user, $bankAccount, 'Zahlung', 'Zahlung', -100.00, '2026-09-01');
        $transaction->transactionCategories()->attach($matchingCategory);

        $service = $this->service();

        // Beide Kandidaten landen exakt auf 28 Punkten (Betrag+Datum unterschiedlich, aber durch das
        // Kategorie-Gewicht ausgeglichen) - ohne den Tiebreak wäre das Ergebnis ein Vorschlag.
        self::assertSame(28.0, $service->calculateScore($transaction, $fixedCostDirectMatch));
        self::assertSame(28.0, $service->calculateScore($transaction, $fixedCostMismatch));

        $outcome = $service->matchTransaction($transaction);

        self::assertSame('linked', $outcome);
        $transaction->refresh();
        self::assertSame($fixedCostDirectMatch->id, $transaction->fixed_cost_id);
    }

    public function test_fixed_cost_without_categories_scores_exactly_as_before(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);
        $category = $this->createCategory('Miete');

        $fixedCost = $this->createFixedCost($user, 'Miete', -1000.00, [
            FixedCost::next_booking_date => '2026-09-01',
        ]);

        $transaction = $this->createTransaction($user, $bankAccount, 'Vermieter GmbH', 'Miete September', -1000.00, '2026-09-01');
        $transaction->transactionCategories()->attach($category);

        // Betrag (60) + Text (30) + Datum (10) = 100 - unverändert, da die Fixkosten keine
        // Transaktions-Kategorien verknüpft haben.
        self::assertSame(100.0, $this->service()->calculateScore($transaction, $fixedCost));
    }

    public function test_uncategorized_transaction_leaves_the_category_component_inactive(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);
        $category = $this->createCategory('Miete');

        $fixedCost = $this->createFixedCost($user, 'Miete', -1000.00, [
            FixedCost::next_booking_date => '2026-09-01',
        ]);
        $fixedCost->transactionCategories()->attach($category);

        $transaction = $this->createTransaction($user, $bankAccount, 'Vermieter GmbH', 'Miete September', -1000.00, '2026-09-01');

        self::assertSame(100.0, $this->service()->calculateScore($transaction, $fixedCost));
    }

    public function test_subcategory_only_counts_when_include_subcategories_is_enabled(): void
    {
        $this->setSettings(['matching_category_weight' => 15]);

        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);
        $parentCategory = $this->createCategory('Versicherungen');
        $childCategory = $this->createCategory('KFZ-Versicherung', $parentCategory->id);

        $fixedCostWithSubcategories = $this->createFixedCost($user, ' ', -50.00, [
            FixedCost::include_subcategories => true,
        ]);
        $fixedCostWithSubcategories->transactionCategories()->attach($parentCategory);

        $fixedCostWithoutSubcategories = $this->createFixedCost($user, ' ', -50.00, [
            FixedCost::include_subcategories => false,
        ]);
        $fixedCostWithoutSubcategories->transactionCategories()->attach($parentCategory);

        $transaction = $this->createTransaction($user, $bankAccount, 'Versicherung AG', 'Beitrag', -50.00);
        $transaction->transactionCategories()->attach($childCategory);

        $service = $this->service();

        // Betrag exakt (60) + Text 0 + Datum 0 = 60 Basis-Score.
        // Treffer nur über die Unterkategorie -> 70% des Kategorie-Gewichts (15 * 0.7 = 10.5).
        self::assertSame(round(60 * 0.85 + 10.5, 2), $service->calculateScore($transaction, $fixedCostWithSubcategories));

        // Ohne include_subcategories zählt die Unterkategorie nicht als Treffer (0 Kategorie-Punkte);
        // die Reskalierung des Basis-Scores wirkt trotzdem, da beide Seiten Kategorien haben (60 * 0.85 = 51).
        self::assertSame(51.0, $service->calculateScore($transaction, $fixedCostWithoutSubcategories));
    }

    public function test_category_mismatch_blocks_the_auto_link_and_creates_a_suggestion_instead(): void
    {
        $this->setSettings(['matching_category_mismatch_blocks_auto_link' => true]);

        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);
        $fixedCostCategory = $this->createCategory('Versicherung');
        $transactionCategory = $this->createCategory('Miete');

        $fixedCost = $this->createFixedCost($user, 'Miete', -1000.00, [
            FixedCost::next_booking_date => '2026-09-01',
        ]);
        $fixedCost->transactionCategories()->attach($fixedCostCategory);

        $transaction = $this->createTransaction($user, $bankAccount, 'Vermieter GmbH', 'Miete September', -1000.00, '2026-09-01');
        $transaction->transactionCategories()->attach($transactionCategory);

        $outcome = $this->service()->matchTransaction($transaction);

        self::assertSame('suggestion_created', $outcome);
        $transaction->refresh();
        self::assertNull($transaction->fixed_cost_id);
        $this->assertDatabaseHas(TransactionMatchingSuggestion::TABLE, [
            TransactionMatchingSuggestion::transaction_id => $transaction->id,
            TransactionMatchingSuggestion::fixed_cost_id => $fixedCost->id,
        ]);
    }

    public function test_category_mismatch_does_not_block_the_auto_link_when_setting_is_disabled(): void
    {
        $this->setSettings(['matching_category_mismatch_blocks_auto_link' => false]);

        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);
        $fixedCostCategory = $this->createCategory('Versicherung');
        $transactionCategory = $this->createCategory('Miete');

        $fixedCost = $this->createFixedCost($user, 'Miete', -1000.00, [
            FixedCost::next_booking_date => '2026-09-01',
        ]);
        $fixedCost->transactionCategories()->attach($fixedCostCategory);

        $transaction = $this->createTransaction($user, $bankAccount, 'Vermieter GmbH', 'Miete September', -1000.00, '2026-09-01');
        $transaction->transactionCategories()->attach($transactionCategory);

        $outcome = $this->service()->matchTransaction($transaction);

        self::assertSame('linked', $outcome);
        $transaction->refresh();
        self::assertSame($fixedCost->id, $transaction->fixed_cost_id);
    }

    public function test_learned_rule_still_takes_precedence_over_the_category_component(): void
    {
        $user = User::factory()->create();
        $bankAccount = $this->createBankAccount($user);
        $matchingCategory = $this->createCategory('Miete');

        // Würde ohne die gelernte Regel dank Kategorie-Treffer gewinnen ...
        $fixedCostWithCategory = $this->createFixedCost($user, 'Miete', -1000.00);
        $fixedCostWithCategory->transactionCategories()->attach($matchingCategory);

        // ... gewinnt aber durch die gelernte Regel, die Vorrang vor dem Scoring hat.
        $fixedCostFromLearnedRule = $this->createFixedCost($user, 'Anderer Name', -1000.00);

        FixedCostMatchingRule::query()->create([
            FixedCostMatchingRule::user_id => $user->id,
            FixedCostMatchingRule::fixed_cost_id => $fixedCostFromLearnedRule->id,
            FixedCostMatchingRule::fingerprint => sha1('payer|vermieter gmbh'),
            FixedCostMatchingRule::payer_token => 'vermieter gmbh',
            FixedCostMatchingRule::purpose_token => null,
            FixedCostMatchingRule::amount_sign => -1,
            FixedCostMatchingRule::amount_min => 900.0,
            FixedCostMatchingRule::amount_max => 1100.0,
            FixedCostMatchingRule::positive_weight => 100.0,
            FixedCostMatchingRule::negative_weight => 0.0,
            FixedCostMatchingRule::last_source => FixedCostMatchingRule::SOURCE_SUGGESTION_ACCEPT,
        ]);

        $transaction = $this->createTransaction($user, $bankAccount, 'Vermieter GmbH', 'Miete September', -1000.00, '2026-09-01');
        $transaction->transactionCategories()->attach($matchingCategory);

        $outcome = $this->service()->matchTransaction($transaction);

        self::assertSame('linked', $outcome);
        $transaction->refresh();
        self::assertSame($fixedCostFromLearnedRule->id, $transaction->fixed_cost_id);
    }

    private function service(): TransactionFixedCostMatchingService
    {
        return app(TransactionFixedCostMatchingService::class);
    }

    private function setSettings(array $values): void
    {
        $settings = app(FixedCostSettings::class);

        foreach ($values as $key => $value) {
            $settings->{$key} = $value;
        }

        $settings->save();
    }

    private function createBankAccount(User $user): BankAccount
    {
        return BankAccount::query()->create([
            BankAccount::name => 'Testkonto',
            BankAccount::user_id => $user->id,
        ]);
    }

    private function createCategory(string $name, ?int $parentId = null): TransactionCategory
    {
        return TransactionCategory::query()->create([
            TransactionCategory::user_id => null,
            TransactionCategory::name => $name,
            TransactionCategory::parent_id => $parentId,
            TransactionCategory::active => true,
        ]);
    }

    private function createFixedCost(User $user, string $name, float $amount, array $overrides = []): FixedCost
    {
        return FixedCost::query()->create(array_merge([
            FixedCost::user_id => $user->id,
            FixedCost::name => $name,
            FixedCost::amount => $amount,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => null,
        ], $overrides));
    }

    private function createTransaction(
        User        $user,
        BankAccount $bankAccount,
        string      $payer,
        string      $purpose,
        float       $amount,
        string      $date = '2026-09-01',
    ): Transaction
    {
        $data = [
            Transaction::date => $date,
            Transaction::value_date => $date,
            Transaction::payer => $payer,
            Transaction::description => $purpose,
            Transaction::purpose => $purpose,
            Transaction::amount => $amount,
            Transaction::balance => 500.00,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
            Transaction::bank_account_id => $bankAccount->id,
            Transaction::user_id => $user->id,
        ];
        $data[Transaction::hash] = Transaction::createHash($data);

        return Transaction::query()->create($data);
    }
}
