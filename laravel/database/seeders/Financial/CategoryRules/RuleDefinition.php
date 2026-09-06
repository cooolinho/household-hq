<?php

namespace Database\Seeders\Financial\CategoryRules;

use App\Models\Financial\TransactionCategoryRule;

/**
 * Beschreibt eine einzelne Kategorieregel inklusive ihrer Kriterien.
 *
 * Der Key ist die stabile Identität der Regel beim (Re-)Seeding und wird in der
 * Spalte financial_transaction_category_rules.key gespeichert.
 */
final readonly class RuleDefinition
{
    /**
     * @param list<CriterionDefinition> $criteria
     */
    public function __construct(
        public string $key,
        public string $operator,
        public array  $criteria,
    )
    {
    }

    /**
     * Mindestens ein Kriterium muss passen.
     */
    public static function anyOf(string $key, CriterionDefinition ...$criteria): self
    {
        return new self($key, TransactionCategoryRule::OPERATOR_OR, array_values($criteria));
    }

    /**
     * Alle Kriterien müssen passen.
     */
    public static function allOf(string $key, CriterionDefinition ...$criteria): self
    {
        return new self($key, TransactionCategoryRule::OPERATOR_AND, array_values($criteria));
    }
}
