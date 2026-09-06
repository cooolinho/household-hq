<?php

namespace Database\Seeders\Financial\CategoryRules;

use App\Models\Financial\TransactionCategoryCriterion;

/**
 * Beschreibt ein einzelnes Kriterium einer Kategorieregel.
 */
final readonly class CriterionDefinition
{
    public function __construct(
        public string  $field,
        public string  $operator,
        public string  $value,
        public ?string $valueSecondary = null,
        public bool    $caseSensitive = false,
    )
    {
    }

    public static function regex(string $field, string $pattern): self
    {
        return new self($field, TransactionCategoryCriterion::OP_REGEX, $pattern);
    }

    public static function amountGreaterThan(float $value): self
    {
        return new self(
            TransactionCategoryCriterion::FIELD_AMOUNT,
            TransactionCategoryCriterion::OP_GREATER_THAN,
            (string)$value,
        );
    }

    public static function amountLessThan(float $value): self
    {
        return new self(
            TransactionCategoryCriterion::FIELD_AMOUNT,
            TransactionCategoryCriterion::OP_LESS_THAN,
            (string)$value,
        );
    }

    /**
     * @return array<string, string|bool|null>
     */
    public function toAttributes(): array
    {
        return [
            TransactionCategoryCriterion::field => $this->field,
            TransactionCategoryCriterion::operator => $this->operator,
            TransactionCategoryCriterion::value => $this->value,
            TransactionCategoryCriterion::value_secondary => $this->valueSecondary,
            TransactionCategoryCriterion::case_sensitive => $this->caseSensitive,
        ];
    }
}
