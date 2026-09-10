<?php

namespace Tests\Feature;

use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\Financial\TransactionCategoryRuleUserSetting;
use App\Models\User;
use Database\Seeders\Financial\CategoryRules\CategoryRuleProvider;
use Database\Seeders\Financial\CategoryRules\KeywordPattern;
use Database\Seeders\Financial\TransactionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCategoryRuleSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_subcategory_receives_at_least_one_active_rule_with_criteria(): void
    {
        $this->seed(TransactionCategorySeeder::class);

        $subcategories = TransactionCategory::query()
            ->whereNull(TransactionCategory::user_id)
            ->whereNotNull(TransactionCategory::parent_id)
            ->with(TransactionCategory::has_many_rules . '.' . TransactionCategoryRule::has_many_criteria)
            ->get();

        self::assertCount(
            count(CategoryRuleProvider::allSubcategoryNames()),
            $subcategories,
        );

        foreach ($subcategories as $subcategory) {
            $activeRules = $subcategory->rules->where(TransactionCategoryRule::active, true);

            self::assertNotEmpty(
                $activeRules,
                sprintf('Unterkategorie "%s" hat keine aktive Regel.', $subcategory->name),
            );

            foreach ($activeRules as $rule) {
                self::assertNotEmpty(
                    $rule->criteria,
                    sprintf('Regel "%s" hat keine Kriterien.', (string)$rule->key),
                );
            }
        }
    }

    public function test_every_seeded_rule_is_an_include_rule(): void
    {
        $this->seed(TransactionCategorySeeder::class);

        $types = TransactionCategoryRule::query()
            ->whereNull(TransactionCategoryRule::user_id)
            ->pluck(TransactionCategoryRule::type)
            ->unique();

        self::assertNotEmpty($types);
        self::assertSame(
            [TransactionCategoryRule::TYPE_INCLUDE],
            $types->values()->all(),
            'Geseedete Systemregeln müssen ausschließlich Include-Regeln sein, niemals Blacklist-Regeln.',
        );
    }

    public function test_every_seeded_rule_has_a_unique_key(): void
    {
        $this->seed(TransactionCategorySeeder::class);

        $keys = TransactionCategoryRule::query()
            ->whereNull(TransactionCategoryRule::user_id)
            ->pluck(TransactionCategoryRule::key);

        self::assertNotEmpty($keys);
        self::assertFalse($keys->contains(null), 'Es existieren globale Regeln ohne Key.');
        self::assertSame($keys->count(), $keys->unique()->count(), 'Regel-Keys sind nicht eindeutig.');
    }

    public function test_all_regex_criteria_are_valid_patterns(): void
    {
        $this->seed(TransactionCategorySeeder::class);

        $criteria = TransactionCategoryCriterion::query()
            ->where(TransactionCategoryCriterion::operator, TransactionCategoryCriterion::OP_REGEX)
            ->get();

        self::assertNotEmpty($criteria);

        foreach ($criteria as $criterion) {
            self::assertNotFalse(
                @preg_match($criterion->value, 'probe'),
                sprintf('Ungültiges Regex-Pattern: %s', $criterion->value),
            );
        }
    }

    public function test_reseeding_is_idempotent_and_keeps_rule_identity(): void
    {
        $this->seed(TransactionCategorySeeder::class);

        $before = TransactionCategoryRule::query()
            ->orderBy(TransactionCategoryRule::key)
            ->pluck(TransactionCategoryRule::id, TransactionCategoryRule::key)
            ->all();
        $criteriaCount = TransactionCategoryCriterion::query()->count();
        $categoryCount = TransactionCategory::query()->count();

        $this->seed(TransactionCategorySeeder::class);

        $after = TransactionCategoryRule::query()
            ->orderBy(TransactionCategoryRule::key)
            ->pluck(TransactionCategoryRule::id, TransactionCategoryRule::key)
            ->all();

        self::assertSame($before, $after, 'Regeln wurden beim erneuten Seeding neu angelegt.');
        self::assertSame($criteriaCount, TransactionCategoryCriterion::query()->count());
        self::assertSame($categoryCount, TransactionCategory::query()->count());
    }

    public function test_reseeding_keeps_user_rule_settings_and_manual_rules(): void
    {
        $this->seed(TransactionCategorySeeder::class);

        $user = User::factory()->create();
        $rule = TransactionCategoryRule::query()
            ->whereNotNull(TransactionCategoryRule::key)
            ->firstOrFail();

        TransactionCategoryRuleUserSetting::query()->create([
            TransactionCategoryRuleUserSetting::user_id => $user->id,
            TransactionCategoryRuleUserSetting::transaction_category_rule_id => $rule->id,
            TransactionCategoryRuleUserSetting::active => false,
        ]);

        $manualRule = TransactionCategoryRule::query()->create([
            TransactionCategoryRule::transaction_category_id => $rule->transaction_category_id,
            TransactionCategoryRule::user_id => $user->id,
            TransactionCategoryRule::key => null,
            TransactionCategoryRule::operator => TransactionCategoryRule::OPERATOR_OR,
            TransactionCategoryRule::active => true,
        ]);

        $this->seed(TransactionCategorySeeder::class);

        $this->assertDatabaseHas(TransactionCategoryRuleUserSetting::TABLE, [
            TransactionCategoryRuleUserSetting::user_id => $user->id,
            TransactionCategoryRuleUserSetting::transaction_category_rule_id => $rule->id,
            TransactionCategoryRuleUserSetting::active => false,
        ]);
        $this->assertDatabaseHas(TransactionCategoryRule::TABLE, [
            TransactionCategoryRule::id => $manualRule->id,
        ]);
    }

    public function test_obsolete_seeded_rules_are_removed(): void
    {
        $this->seed(TransactionCategorySeeder::class);

        $category = TransactionCategory::query()
            ->whereNull(TransactionCategory::user_id)
            ->whereNotNull(TransactionCategory::parent_id)
            ->firstOrFail();

        $obsolete = TransactionCategoryRule::query()->create([
            TransactionCategoryRule::transaction_category_id => $category->id,
            TransactionCategoryRule::user_id => null,
            TransactionCategoryRule::key => 'veraltete.regel',
            TransactionCategoryRule::operator => TransactionCategoryRule::OPERATOR_OR,
            TransactionCategoryRule::active => true,
        ]);

        $this->seed(TransactionCategorySeeder::class);

        $this->assertDatabaseMissing(TransactionCategoryRule::TABLE, [
            TransactionCategoryRule::id => $obsolete->id,
        ]);
    }

    public function test_keyword_pattern_handles_umlauts_and_meta_characters(): void
    {
        $patterns = KeywordPattern::build(['Öl', 'Café', 'Müller', '1&1', 'e.on', 'disney+']);

        self::assertNotEmpty($patterns);

        $matches = static function (array $patterns, string $subject): bool {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $subject) === 1) {
                    return true;
                }
            }

            return false;
        };

        self::assertTrue($matches($patterns, 'RECHNUNG ÖL LIEFERUNG'));
        self::assertTrue($matches($patterns, 'Café Central'));
        self::assertTrue($matches($patterns, 'MUELLER DROGERIE'), 'Transliteration ue/ae/oe fehlt.');
        self::assertTrue($matches($patterns, 'Rechnung 1&1 Telecom'));
        self::assertTrue($matches($patterns, 'E.ON Energie'));
        self::assertTrue($matches($patterns, 'DISNEY+ ABO'));
        self::assertFalse($matches($patterns, 'Kaufhaus Meier'));
    }

    public function test_short_keywords_stay_strictly_bounded(): void
    {
        $patterns = KeywordPattern::build(['oper', 'zoo', 'norma']);

        foreach (['Operation Zuzahlung', 'Zooplus Bestellung', 'Normalpreis'] as $subject) {
            foreach ($patterns as $pattern) {
                self::assertSame(
                    0,
                    preg_match($pattern, $subject),
                    sprintf('Kurzes Keyword hat "%s" fälschlich getroffen.', $subject),
                );
            }
        }
    }

    public function test_long_keywords_match_german_compounds(): void
    {
        $patterns = KeywordPattern::build(['friseur', 'apotheke']);

        $matches = static function (array $patterns, string $subject): bool {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $subject) === 1) {
                    return true;
                }
            }

            return false;
        };

        self::assertTrue($matches($patterns, 'Friseursalon Anna'));
        self::assertTrue($matches($patterns, 'Apothekenrechnung Nord'));
    }
}
