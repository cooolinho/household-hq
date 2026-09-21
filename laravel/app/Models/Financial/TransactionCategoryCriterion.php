<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class TransactionCategoryCriterion
 *
 * Columns
 * @property int $id
 * @property int $transaction_category_rule_id
 * @property string $field
 * @property string $operator
 * @property string $value
 * @property string|null $value_secondary
 * @property bool $case_sensitive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property TransactionCategoryRule $rule
 */
class TransactionCategoryCriterion extends Model
{
    const string TABLE = 'financial_transaction_category_criteria';

    // field options
    const string FIELD_DATE = 'date';
    const string FIELD_PAYER = 'payer';
    const string FIELD_DESCRIPTION = 'description';
    const string FIELD_PURPOSE = 'purpose';
    const string FIELD_AMOUNT = 'amount';
    const string FIELD_AMOUNT_CURRENCY = 'amount_currency';

    // operator options
    const string OP_EQUALS = 'equals';
    const string OP_CONTAINS = 'contains';
    const string OP_STARTS_WITH = 'starts_with';
    const string OP_ENDS_WITH = 'ends_with';
    const string OP_REGEX = 'regex';
    const string OP_GREATER_THAN = 'greater_than';
    const string OP_LESS_THAN = 'less_than';
    const string OP_BETWEEN = 'between';

    // columns
    const string id = 'id';
    const string transaction_category_rule_id = 'transaction_category_rule_id';
    const string field = 'field';
    const string operator = 'operator';
    const string value = 'value';
    const string value_secondary = 'value_secondary';
    const string case_sensitive = 'case_sensitive';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_rule = 'rule';

    protected $table = self::TABLE;

    protected $fillable = [
        self::transaction_category_rule_id,
        self::field,
        self::operator,
        self::value,
        self::value_secondary,
        self::case_sensitive,
    ];

    protected $casts = [
        self::case_sensitive => 'boolean',
    ];

    /** @return string[] */
    public static function getTextOperators(): array
    {
        return [
            self::OP_EQUALS,
            self::OP_CONTAINS,
            self::OP_STARTS_WITH,
            self::OP_ENDS_WITH,
            self::OP_REGEX,
        ];
    }

    /** @return string[] */
    public static function getNumericOperators(): array
    {
        return [
            self::OP_EQUALS,
            self::OP_GREATER_THAN,
            self::OP_LESS_THAN,
            self::OP_BETWEEN,
        ];
    }

    /** @return string[] */
    public static function getTextFields(): array
    {
        return [
            self::FIELD_PAYER,
            self::FIELD_DESCRIPTION,
            self::FIELD_PURPOSE,
            self::FIELD_AMOUNT_CURRENCY,
        ];
    }

    /** @return string[] */
    public static function getNumericFields(): array
    {
        return [
            self::FIELD_AMOUNT,
        ];
    }

    /** @return string[] */
    public static function getDateFields(): array
    {
        return [
            self::FIELD_DATE,
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(TransactionCategoryRule::class, self::transaction_category_rule_id);
    }
}
