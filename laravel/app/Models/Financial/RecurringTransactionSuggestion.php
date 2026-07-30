<?php

namespace App\Models\Financial;

use App\Models\Enums\RecurringTransactionSuggestionStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class RecurringTransactionSuggestion
 *
 * Columns
 * @property int $id
 * @property int $user_id
 * @property string $fingerprint
 * @property string $payer
 * @property string $purpose
 * @property string|null $name_hint
 * @property float $amount
 * @property string $amount_currency
 * @property int $amount_sign
 * @property int $occurrence_count
 * @property Carbon|null $first_seen_at
 * @property Carbon|null $last_seen_at
 * @property int|null $sample_transaction_id
 * @property RecurringTransactionSuggestionStatusEnum $status
 *
 * Relations
 * @property User $user
 * @property Transaction|null $sampleTransaction
 */
class RecurringTransactionSuggestion extends Model
{
    const string TABLE = 'financial_recurring_transaction_suggestions';

    // columns
    const string id = 'id';
    const string user_id = 'user_id';
    const string fingerprint = 'fingerprint';
    const string payer = 'payer';
    const string purpose = 'purpose';
    const string name_hint = 'name_hint';
    const string amount = 'amount';
    const string amount_currency = 'amount_currency';
    const string amount_sign = 'amount_sign';
    const string occurrence_count = 'occurrence_count';
    const string first_seen_at = 'first_seen_at';
    const string last_seen_at = 'last_seen_at';
    const string sample_transaction_id = 'sample_transaction_id';
    const string status = 'status';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_user = 'user';
    const string belongs_to_sample_transaction = 'sampleTransaction';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::fingerprint,
        self::payer,
        self::purpose,
        self::name_hint,
        self::amount,
        self::amount_currency,
        self::amount_sign,
        self::occurrence_count,
        self::first_seen_at,
        self::last_seen_at,
        self::sample_transaction_id,
        self::status,
    ];

    protected $casts = [
        self::amount => 'float',
        self::amount_sign => 'integer',
        self::occurrence_count => 'integer',
        self::first_seen_at => 'date',
        self::last_seen_at => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sampleTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, self::sample_transaction_id);
    }

    public function isPending(): bool
    {
        return $this->status === RecurringTransactionSuggestionStatusEnum::PENDING->name;
    }
}

