<?php

namespace App\Models;

use App\Models\Enums\BankAccountTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
 */
class BankAccount extends Model
{
    // columns
    const string id = 'id';
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
    const string user_id = 'user_id';
    const string belongs_to_user = 'user';
    const string TABLE = 'bank_accounts';

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
}
