<?php

namespace App\Models\Financial;

use App\Models\Contracts\FinancialBudgetTransactionCategory;
use App\Models\Enums\BudgetIconEnum;
use App\Models\Enums\BudgetPeriodEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Class Budget
 *
 * Columns
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property BudgetIconEnum $icon
 * @property float $amount
 * @property string $currency
 * @property BudgetPeriodEnum $period
 * @property bool $include_subcategories
 * @property int $warning_threshold
 * @property int $critical_threshold
 * @property bool $send_mail
 * @property bool $send_notification
 * @property bool $active
 * @property int $sort
 * @property string|null $last_notified_level
 * @property Carbon|null $last_notified_period_start
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property User $user
 * @property Collection|TransactionCategory[] $transactionCategories
 */
class Budget extends Model
{
    const string TABLE = 'financial_budgets';

    // columns
    const string id = 'id';

    const string user_id = 'user_id';

    const string name = 'name';

    const string description = 'description';

    const string icon = 'icon';

    const string amount = 'amount';

    const string currency = 'currency';

    const string period = 'period';

    const string include_subcategories = 'include_subcategories';

    const string warning_threshold = 'warning_threshold';

    const string critical_threshold = 'critical_threshold';

    const string send_mail = 'send_mail';

    const string send_notification = 'send_notification';

    const string active = 'active';

    const string sort = 'sort';

    const string last_notified_level = 'last_notified_level';

    const string last_notified_period_start = 'last_notified_period_start';

    const string created_at = Model::CREATED_AT;

    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_user = 'user';

    const string belongs_to_many_transaction_categories = 'transactionCategories';

    const string DEFAULT_CURRENCY = 'EUR';

    const int DEFAULT_WARNING_THRESHOLD = 80;

    const int DEFAULT_CRITICAL_THRESHOLD = 100;

    protected $table = self::TABLE;

    /**
     * Spiegelt die Defaults der Migration, damit frisch erstellte Instanzen
     * (z. B. aus Tests oder Seedern) vollständige Schwellwerte besitzen.
     */
    protected $attributes = [
        self::icon => BudgetIconEnum::SHOPPING_CART->name,
        self::currency => self::DEFAULT_CURRENCY,
        self::period => BudgetPeriodEnum::MONTHLY->name,
        self::include_subcategories => true,
        self::warning_threshold => self::DEFAULT_WARNING_THRESHOLD,
        self::critical_threshold => self::DEFAULT_CRITICAL_THRESHOLD,
        self::send_mail => false,
        self::send_notification => true,
        self::active => true,
        self::sort => 0,
    ];

    protected $fillable = [
        self::user_id,
        self::name,
        self::description,
        self::icon,
        self::amount,
        self::currency,
        self::period,
        self::include_subcategories,
        self::warning_threshold,
        self::critical_threshold,
        self::send_mail,
        self::send_notification,
        self::active,
        self::sort,
        self::last_notified_level,
        self::last_notified_period_start,
    ];

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
     * Alle Kategorie-IDs, deren Transaktionen auf dieses Budget einzahlen.
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
            FinancialBudgetTransactionCategory::PIVOT_TABLE,
            FinancialBudgetTransactionCategory::BUDGET_ID,
            FinancialBudgetTransactionCategory::CATEGORY_ID,
        );
    }

    protected function casts(): array
    {
        return [
            self::icon => BudgetIconEnum::class,
            self::amount => 'float',
            self::period => BudgetPeriodEnum::class,
            self::include_subcategories => 'boolean',
            self::warning_threshold => 'integer',
            self::critical_threshold => 'integer',
            self::send_mail => 'boolean',
            self::send_notification => 'boolean',
            self::active => 'boolean',
            self::sort => 'integer',
            self::last_notified_period_start => 'date',
        ];
    }
}
