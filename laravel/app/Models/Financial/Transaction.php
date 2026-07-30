<?php

namespace App\Models\Financial;

use App\Models\User;
use Database\Factories\Financial\TransactionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Tags\HasTags;

/**
 * Class Transaction
 *
 * Columns
 * @property int $id
 * @property string $date
 * @property string $value_date
 * @property string $payer
 * @property string $description
 * @property string $purpose
 * @property float $balance
 * @property string $balance_currency
 * @property float $amount
 * @property string $amount_currency
 * @property int $bank_account_id
 * @property int $user_id
 * @property int|null $fixed_cost_id
 *
 * // relations
 * @property User $user
 * @property BankAccount $bankAccount
 * @property FixedCost|null $fixedCost
 * @property Collection|TransactionMatchingSuggestion[] $matchingSuggestions
 */
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;
    use HasTags;

    const string TABLE = 'financial_transactions';

    // columns
    const string id = 'id';
    const string date = 'date';
    const string value_date = 'value_date';
    const string payer = 'payer';
    const string description = 'description';
    const string purpose = 'purpose';
    const string balance = 'balance';
    const string balance_currency = 'balance_currency';
    const string amount = 'amount';
    const string amount_currency = 'amount_currency';
    const string bank_account_id = 'bank_account_id';
    const string user_id = 'user_id';
    const string hash = 'hash';
    const string fixed_cost_id = 'fixed_cost_id';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_bank_account = 'bankAccount';
    const string belongs_to_user = 'user';
    const string belongs_to_fixed_cost = 'fixedCost';
    const string has_many_matching_suggestions = 'matchingSuggestions';
    const string morph_to_many_tags = 'tags';

    protected $table = self::TABLE;

    protected $fillable = [
        self::date,
        self::value_date,
        self::payer,
        self::description,
        self::purpose,
        self::balance,
        self::balance_currency,
        self::amount,
        self::amount_currency,
        self::bank_account_id,
        self::user_id,
        self::fixed_cost_id,
    ];

    protected $casts = [
        self::date => 'datetime',
        self::value_date => 'datetime',
        self::balance => 'float',
        self::amount => 'float',
    ];

    public static function createHash(array $transactionData): string
    {
        return md5(sprintf(
            '%s|%s|%s|%s|%s|%s|%s|%s',
            $transactionData[Transaction::date],
            $transactionData[Transaction::amount],
            $transactionData[Transaction::value_date],
            $transactionData[Transaction::payer] ?? '',
            $transactionData[Transaction::description] ?? '',
            $transactionData[Transaction::purpose] ?? '',
            $transactionData[Transaction::balance] ?? '',
            $transactionData[Transaction::user_id] ?? ''
        ));
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, self::bank_account_id);
    }

    public function fixedCost(): BelongsTo
    {
        return $this->belongsTo(FixedCost::class, self::fixed_cost_id);
    }

    public function matchingSuggestions(): HasMany
    {
        return $this->hasMany(TransactionMatchingSuggestion::class, TransactionMatchingSuggestion::transaction_id);
    }
}
