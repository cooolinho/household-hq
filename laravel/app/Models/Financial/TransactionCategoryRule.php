<?php

namespace App\Models\Financial;

use App\Models\User;
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
 * @property int|null $user_id
 * @property string|null $key  Stabiler Slug systemseitig geseedeter Regeln (null = manuell angelegt)
 * @property string $operator  AND|OR
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property TransactionCategory $category
 * @property User|null $user
 * @property Collection|TransactionCategoryCriterion[] $criteria
 * @property Collection|TransactionCategoryRuleUserSetting[] $userSettings
 */
class TransactionCategoryRule extends Model
{
    const string TABLE = 'financial_transaction_category_rules';

    const string OPERATOR_AND = 'AND';
    const string OPERATOR_OR = 'OR';

    // columns
    const string id = 'id';
    const string transaction_category_id = 'transaction_category_id';
    const string user_id = 'user_id';
    const string key = 'key';
    const string operator = 'operator';
    const string active = 'active';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_category = 'category';
    const string belongs_to_user = 'user';
    const string has_many_criteria = 'criteria';
    const string has_many_user_settings = 'userSettings';

    protected $table = self::TABLE;

    protected $fillable = [
        self::transaction_category_id,
        self::user_id,
        self::key,
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(TransactionCategoryCriterion::class, TransactionCategoryCriterion::transaction_category_rule_id);
    }

    public function userSettings(): HasMany
    {
        return $this->hasMany(
            TransactionCategoryRuleUserSetting::class,
            TransactionCategoryRuleUserSetting::transaction_category_rule_id
        );
    }
}
