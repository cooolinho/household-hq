<?php

namespace App\Models\Financial;

use App\Models\Enums\BankAccountTypeEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Tags\HasTags;

/**
 * Class BankAccount
 *
 * Columns
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property string|null $account_holder
 * @property string|null $iban
 * @property string|null $bic
 * @property string|null $bank_name
 * @property float|null $balance
 * @property Carbon|null $balance_date
 * @property BankAccountTypeEnum|null $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property User $user
 * @property Transaction[] $transactions
 */
class BankAccount extends Model
{
    use HasTags;

    const string TABLE = 'financial_bank_accounts';

    // columns
    const string id = 'id';
    const string user_id = 'user_id';
    const string name = 'name';
    const string account_holder = 'account_holder';
    const string iban = 'iban';
    const string bic = 'bic';
    const string bank_name = 'bank_name';
    const string balance = 'balance';
    const string balance_date = 'balance_date';
    const string type = 'type';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_user = 'user';
    const string has_many_transactions = 'transactions';
    const string morph_to_many_tags = 'tags';

    protected $table = self::TABLE;
    protected $fillable = [
        self::name,
        self::user_id,
        self::account_holder,
        self::iban,
        self::bic,
        self::bank_name,
        self::balance,
        self::balance_date,
        self::type,
    ];

    protected $casts = [
        self::type => BankAccountTypeEnum::class,
        self::balance_date => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, Transaction::bank_account_id);
    }

    public function updateBalance(): self
    {
        /** @var Transaction $lastTransaction */
        $lastTransaction = $this->transactions()
            ->orderBy(Transaction::date, 'desc')
            ->get()
            ->first();

        if ($lastTransaction) {
            $this->balance = $lastTransaction->balance;
            $this->balance_date = $lastTransaction->date;
            $this->save();
        }

        return $this;
    }
}
