<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class TransactionCategoryRule
 *
 * Columns
 * @property int $id
 * @property int $transaction_category_id
 * @property string $operator  AND|OR
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property TransactionCategory $category
 * @property Collection|TransactionCategoryCriterion[] $criteria
 */
class TransactionCategoryRule extends Model
{
    const string TABLE = 'financial_transaction_category_rules';

    const string OPERATOR_AND = 'AND';
    const string OPERATOR_OR = 'OR';

    // columns
    const string id = 'id';
    const string transaction_category_id = 'transaction_category_id';
    const string operator = 'operator';
    const string active = 'active';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_category = 'category';
    const string has_many_criteria = 'criteria';

    protected $table = self::TABLE;

    protected $fillable = [
        self::transaction_category_id,
        self::operator,
        self::active,
    ];

    protected $casts = [
        self::active => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, self::transaction_category_id);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(TransactionCategoryCriterion::class, TransactionCategoryCriterion::transaction_category_rule_id);
    }
}
