<?php

namespace App\Models\Financial;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 *
 * // relations
 * @property User $user
 * @property BankAccount $bankAccount
 */
class Transaction extends Model
{
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
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_bank_account = 'bankAccount';
    const string belongs_to_user = 'user';
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
    ];

    protected $casts = [
        self::date => 'datetime',
        self::value_date => 'datetime',
        self::balance => 'float',
        self::amount => 'float',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, self::bank_account_id);
    }
}
