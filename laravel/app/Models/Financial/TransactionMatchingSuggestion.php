<?php

namespace App\Models\Financial;

use App\Models\Enums\MatchingSuggestionStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TransactionMatchingSuggestion
 *
 * Columns
 * @property int $id
 * @property int $transaction_id
 * @property int $fixed_cost_id
 * @property float $score
 * @property string $status  (PENDING|ACCEPTED|REJECTED)
 *
 * Relations
 * @property Transaction $transaction
 * @property FixedCost $fixedCost
 */
class TransactionMatchingSuggestion extends Model
{
    const string TABLE = 'financial_transaction_matching_suggestions';

    // columns
    const string id = 'id';
    const string transaction_id = 'transaction_id';
    const string fixed_cost_id = 'fixed_cost_id';
    const string score = 'score';
    const string status = 'status';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_transaction = 'transaction';
    const string belongs_to_fixed_cost = 'fixedCost';

    protected $table = self::TABLE;

    protected $fillable = [
        self::transaction_id,
        self::fixed_cost_id,
        self::score,
        self::status,
    ];

    protected $casts = [
        self::score => 'float',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function fixedCost(): BelongsTo
    {
        return $this->belongsTo(FixedCost::class);
    }

    public function isPending(): bool
    {
        return $this->status === MatchingSuggestionStatusEnum::PENDING->name;
    }
}

