<?php

namespace App\Models\Financial;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class TransactionCategoryRuleUserSetting
 *
 * Columns
 * @property int $id
 * @property int $user_id
 * @property int $transaction_category_rule_id
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property User $user
 * @property TransactionCategoryRule $rule
 */
class TransactionCategoryRuleUserSetting extends Model
{
    const string TABLE = 'financial_transaction_category_rule_user_settings';

    // columns
    const string id = 'id';
    const string user_id = 'user_id';
    const string transaction_category_rule_id = 'transaction_category_rule_id';
    const string active = 'active';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_user = 'user';
    const string belongs_to_rule = 'rule';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::transaction_category_rule_id,
        self::active,
    ];

    protected $casts = [
        self::active => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(TransactionCategoryRule::class, self::transaction_category_rule_id);
    }
}
