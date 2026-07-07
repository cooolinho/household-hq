<?php

namespace App\Models\Financial;

use App\Models\Document;
use App\Models\Enums\FixedCostCategoryEnum;
use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Tags\HasTags;

/**
 * Class FixedCost
 *
 * Columns
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property float $amount
 * @property FixedCostCategoryEnum $category
 * @property FixedCostIntervalEnum $interval
 * @property FixedCostEndsModeEnum $ends_mode
 * @property Carbon|null $ends_date
 * @property FixedCostIntervalEnum|null $ends_interval
 * @property Carbon|null $extended_date
 * @property FixedCostIntervalEnum|null $extended_interval
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property User $user
 * @property Collection|Document[] $has_many_documents
 */
class FixedCost extends Model
{
    use HasTags;

    const string TABLE = 'financial_fixed_costs';

    // columns
    const string user_id = 'user_id';
    const string id = 'id';
    const string name = 'name';
    const string amount = 'amount';
    const string category = 'category';
    const string interval = 'interval';
    const string ends_mode = 'ends_mode'; // End- / Verlängerungsmodus (ended|extended)
    const string ends_date = 'ends_date';
    const string ends_interval = 'ends_interval';
    const string extended_date = 'extended_date';
    const string extended_interval = 'extended_interval';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string has_many_documents = 'has_many_documents';
    const string belongs_to_user = 'user';
    const string morph_to_many_tags = 'tags';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::name,
        self::amount,
        self::category,
        self::interval,
        self::ends_mode,
        self::ends_date,
        self::ends_interval,
        self::extended_date,
        self::extended_interval,
    ];

    protected $casts = [
        self::amount => 'decimal:2',
        self::ends_date => 'date',
        self::extended_date => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
