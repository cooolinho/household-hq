<?php

namespace Database\Seeders\Financial;

use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use Database\Seeders\Financial\CategoryRules\CategoryRuleProvider;
use Database\Seeders\Financial\CategoryRules\RuleDefinition;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Seeder;

/**
 * TransactionCategorySeeder
 *
 * Legt die globalen Transaktionskategorien inklusive ihrer Erkennungsregeln an.
 *
 * Die Fachdaten liegen nicht hier, sondern in je einer Provider-Klasse pro
 * Hauptkategorie unter Database\Seeders\Financial\CategoryRules:
 *  - FinanceInsuranceRules      → Finanzen & Versicherungen
 *  - LeisureEntertainmentRules  → Freizeit & Unterhaltung
 *  - LivingExpensesRules        → Lebenshaltung
 *  - MobilityRules              → Mobilität
 *  - GovernmentRules            → Staat & Behörde
 *
 * Regeln werden über ihren stabilen Key synchronisiert: bestehende Systemregeln
 * behalten ihre ID (und damit die nutzerseitigen Deaktivierungen), veraltete
 * Systemregeln werden entfernt. Manuell angelegte Regeln (key = null) bleiben
 * unangetastet.
 */
class TransactionCategorySeeder extends Seeder
{
    public static function description(): string
    {
        return 'Legt die Transaktionskategorien mit ihren Unterkategorien und Erkennungsregeln an';
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public static function dependencies(): array
    {
        return [];
    }

    public function run(): void
    {
        foreach (CategoryRuleProvider::all() as $provider) {
            $parent = $this->createCategory($provider::parentName(), null);

            foreach (array_keys($provider::subcategories()) as $childName) {
                $child = $this->createCategory($childName, $parent->getKey());
                $this->syncRules($child, $provider::rulesFor($childName));
            }
        }
    }

    private function createCategory(string $name, ?int $parentId): TransactionCategory
    {
        return TransactionCategory::query()->firstOrCreate(
            [
                TransactionCategory::user_id => null,
                TransactionCategory::name => $name,
                TransactionCategory::parent_id => $parentId,
            ],
            [
                TransactionCategory::active => true,
            ],
        );
    }

    /**
     * @param list<RuleDefinition> $definitions
     */
    private function syncRules(TransactionCategory $category, array $definitions): void
    {
        $keys = [];

        foreach ($definitions as $definition) {
            $rule = TransactionCategoryRule::query()->updateOrCreate(
                [
                    TransactionCategoryRule::key => $definition->key,
                ],
                [
                    TransactionCategoryRule::transaction_category_id => $category->getKey(),
                    TransactionCategoryRule::user_id => null,
                    TransactionCategoryRule::operator => $definition->operator,
                ],
            );

            $this->replaceCriteria($rule, $definition);
            $keys[] = $definition->key;
        }

        $this->deleteObsoleteRules($category, $keys);
    }

    /**
     * Kriterien besitzen keine eigene Identität und werden daher komplett ersetzt –
     * allerdings nur, wenn sie sich tatsächlich unterscheiden, damit unveränderte
     * Regeln keine überflüssigen Schreibvorgänge auslösen.
     */
    private function replaceCriteria(TransactionCategoryRule $rule, RuleDefinition $definition): void
    {
        $target = array_map(
            static fn($criterion): array => $criterion->toAttributes(),
            $definition->criteria,
        );

        $current = TransactionCategoryCriterion::query()
            ->where(TransactionCategoryCriterion::transaction_category_rule_id, $rule->getKey())
            ->orderBy(TransactionCategoryCriterion::id)
            ->get()
            ->map(static fn(TransactionCategoryCriterion $criterion): array => [
                TransactionCategoryCriterion::field => $criterion->field,
                TransactionCategoryCriterion::operator => $criterion->operator,
                TransactionCategoryCriterion::value => $criterion->value,
                TransactionCategoryCriterion::value_secondary => $criterion->value_secondary,
                TransactionCategoryCriterion::case_sensitive => $criterion->case_sensitive,
            ])
            ->all();

        if ($current === $target) {
            return;
        }

        TransactionCategoryCriterion::query()
            ->where(TransactionCategoryCriterion::transaction_category_rule_id, $rule->getKey())
            ->delete();

        foreach ($target as $attributes) {
            TransactionCategoryCriterion::query()->create([
                TransactionCategoryCriterion::transaction_category_rule_id => $rule->getKey(),
                ...$attributes,
            ]);
        }
    }

    /**
     * Entfernt Systemregeln der Kategorie, die es in der aktuellen Definition nicht mehr gibt.
     * Manuell angelegte Regeln (key = null) bleiben erhalten.
     *
     * @param list<string> $keys
     */
    private function deleteObsoleteRules(TransactionCategory $category, array $keys): void
    {
        TransactionCategoryRule::query()
            ->where(TransactionCategoryRule::transaction_category_id, $category->getKey())
            ->whereNotNull(TransactionCategoryRule::key)
            ->when(
                $keys !== [],
                static fn(Builder $query): Builder => $query->whereNotIn(TransactionCategoryRule::key, $keys),
            )
            ->delete();
    }
}
