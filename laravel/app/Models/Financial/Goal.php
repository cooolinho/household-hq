<?php

namespace App\Models\Financial;

use App\AppConfig;
use App\Models\Contracts\FinancialGoalTransactionCategory;
use App\Models\Enums\GoalDirectionEnum;
use App\Models\Enums\GoalIconEnum;
use App\Models\Enums\GoalTypeEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Class Goal
 *
 * Columns
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property GoalIconEnum $icon
 * @property GoalTypeEnum $type
 * @property GoalDirectionEnum $direction
 * @property float $start_amount
 * @property float $target_amount
 * @property string $currency
 * @property bool $include_subcategories
 * @property Carbon $start_date
 * @property Carbon|null $target_date
 * @property string|null $image_path
 * @property bool $active
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property User $user
 * @property Collection|TransactionCategory[] $transactionCategories
 * @property Collection|GoalContribution[] $contributions
 */
class Goal extends Model
{
    const string TABLE = 'financial_goals';

    // columns
    const string id = 'id';

    const string user_id = 'user_id';

    const string name = 'name';

    const string description = 'description';

    const string icon = 'icon';

    const string type = 'type';

    const string direction = 'direction';

    const string start_amount = 'start_amount';

    const string target_amount = 'target_amount';

    const string currency = 'currency';

    const string include_subcategories = 'include_subcategories';

    const string start_date = 'start_date';

    const string target_date = 'target_date';

    const string image_path = 'image_path';

    const string active = 'active';

    const string sort = 'sort';

    const string created_at = Model::CREATED_AT;

    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_user = 'user';

    const string belongs_to_many_transaction_categories = 'transactionCategories';

    const string has_many_contributions = 'contributions';

    const string DEFAULT_CURRENCY = 'EUR';

    const string STORAGE_DISK = AppConfig::FILESYSTEM_GOAL_IMAGES;

    protected $table = self::TABLE;

    /**
     * Spiegelt die Defaults der Migration, damit frisch erstellte Instanzen
     * (z. B. aus Tests oder Seedern) vollständige Werte besitzen.
     */
    protected $attributes = [
        self::icon => GoalIconEnum::TROPHY->name,
        self::type => GoalTypeEnum::SAVINGS->name,
        self::direction => GoalDirectionEnum::EXPENSE->name,
        self::start_amount => 0,
        self::currency => self::DEFAULT_CURRENCY,
        self::include_subcategories => true,
        self::active => true,
        self::sort => 0,
    ];

    protected $fillable = [
        self::user_id,
        self::name,
        self::description,
        self::icon,
        self::type,
        self::direction,
        self::start_amount,
        self::target_amount,
        self::currency,
        self::include_subcategories,
        self::start_date,
        self::target_date,
        self::image_path,
        self::active,
        self::sort,
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $goal): void {
            if (filled($goal->image_path)) {
                Storage::disk(self::STORAGE_DISK)->delete($goal->image_path);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeActiveForUser(Builder $query, int $userId): Builder
    {
        return $query
            ->where(self::user_id, $userId)
            ->where(self::active, true)
            ->orderBy(self::sort)
            ->orderBy(self::id);
    }

    /**
     * Alle Kategorie-IDs, deren Transaktionen auf dieses Ziel einzahlen.
     * Bei include_subcategories werden die Unterkategorien der verknüpften Kategorien ergänzt.
     *
     * @return list<int>
     */
    public function resolveCategoryIds(): array
    {
        $categories = $this->relationLoaded(self::belongs_to_many_transaction_categories)
            ? $this->transactionCategories
            : $this->transactionCategories()->get();

        $categoryIds = [];

        foreach ($categories as $category) {
            $categoryIds[] = (int)$category->getKey();

            if ($this->include_subcategories) {
                $categoryIds = [...$categoryIds, ...$category->getDescendantIds()];
            }
        }

        return array_values(array_unique($categoryIds));
    }

    public function transactionCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            TransactionCategory::class,
            FinancialGoalTransactionCategory::PIVOT_TABLE,
            FinancialGoalTransactionCategory::GOAL_ID,
            FinancialGoalTransactionCategory::CATEGORY_ID,
        );
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class, GoalContribution::goal_id);
    }

    /**
     * Betragsspanne zwischen Start- und Zielbetrag; Nenner der Fortschrittsberechnung.
     */
    public function span(): float
    {
        return abs($this->target_amount - $this->start_amount);
    }

    public function imageUrl(): ?string
    {
        if (blank($this->image_path)) {
            return null;
        }

        return route('app.goals.image', ['goal' => $this->getKey()]);
    }

    protected function casts(): array
    {
        return [
            self::icon => GoalIconEnum::class,
            self::type => GoalTypeEnum::class,
            self::direction => GoalDirectionEnum::class,
            self::start_amount => 'float',
            self::target_amount => 'float',
            self::include_subcategories => 'boolean',
            self::start_date => 'date',
            self::target_date => 'date',
            self::active => 'boolean',
            self::sort => 'integer',
        ];
    }
}
