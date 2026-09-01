<?php

namespace App\Models\Financial;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class TransactionCategory
 *
 * Columns
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property int|null $parent_id
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property User $user
 * @property TransactionCategory|null $parent
 * @property Collection|TransactionCategory[] $children
 * @property Collection|TransactionCategoryRule[] $rules
 * @property Collection|Transaction[] $transactions
 */
class TransactionCategory extends Model
{
    const string TABLE = 'financial_transaction_categories';

    // columns
    const string id = 'id';
    const string user_id = 'user_id';
    const string name = 'name';
    const string parent_id = 'parent_id';
    const string active = 'active';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_user = 'user';
    const string belongs_to_parent = 'parent';
    const string has_many_children = 'children';
    const string has_many_rules = 'rules';
    const string belongs_to_many_transactions = 'transactions';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::name,
        self::parent_id,
        self::active,
    ];

    protected $casts = [
        self::active => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, self::parent_id);
    }

    public function children(): HasMany
    {
        return $this->hasMany(TransactionCategory::class, self::parent_id);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(TransactionCategoryRule::class, TransactionCategoryRule::transaction_category_id);
    }

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(
            Transaction::class,
            'financial_transaction_transaction_category',
            'transaction_category_id',
            'transaction_id'
        )->withPivot('created_at');
    }

    public function getFullNameAttribute(): string
    {
        if ($this->parent_id !== null && $this->relationLoaded(self::belongs_to_parent) && $this->parent !== null) {
            return $this->parent->name . ' > ' . $this->name;
        }

        return $this->name;
    }

    public function isGlobal(): bool
    {
        return $this->user_id === null;
    }

    public function scopeVisibleForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId) {
            $query->where(self::user_id, $userId)
                ->orWhereNull(self::user_id);
        });
    }
}