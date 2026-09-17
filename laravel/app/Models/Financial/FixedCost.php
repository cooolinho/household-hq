<?php

namespace App\Models\Financial;

use App\Models\CommentableInterface;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasReminders;
use App\Models\Contracts\Documentables;
use App\Models\Contracts\FinancialFixedCostTransactionCategory;
use App\Models\Document;
use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Spatie\Tags\HasTags;

/**
 * Class FixedCost
 *
 * Columns
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $notes
 * @property float $amount
 * @property int|null $category_id
 * @property bool $include_subcategories
 * @property FixedCostIntervalEnum $interval
 * @property FixedCostEndsModeEnum $ends_mode
 * @property Carbon|null $ends_date
 * @property Carbon|null $extended_date
 * @property FixedCostIntervalEnum|null $extended_interval
 * @property int|null $custom_interval_value
 * @property string|null $custom_interval_unit
 * @property int|null $custom_extended_interval_value
 * @property string|null $custom_extended_interval_unit
 * @property int $insurance_id
 * @property Carbon|null $next_booking_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property User $user
 * @property FixedCostCategory|null $category
 * @property Collection|Document[] $documents
 * @property Insurance|null $insurance
 * @property Collection|Transaction[] $transactions
 * @property Collection|TransactionCategory[] $transactionCategories
 * @property Collection|TransactionMatchingSuggestion[] $matchingSuggestions
 * @property Collection|FixedCostMatchingRule[] $matchingRules
 * @property Collection|FixedCostBookingDateSuggestion[] $bookingDateSuggestions
 * @property Collection|\App\Models\Reminder[] $reminders
 */
class FixedCost extends Model implements CommentableInterface
{
    use HasComments;
    use HasTags;
    use HasReminders;

    const string TABLE = 'financial_fixed_costs';

    // columns
    const string user_id = 'user_id';

    const string id = 'id';

    const string name = 'name';

    const string notes = 'notes';

    const string amount = 'amount';

    // legacy column used in historical migration only
    const string category = 'category';

    const string category_id = 'category_id';

    const string include_subcategories = 'include_subcategories';

    const string interval = 'interval';

    const string custom_interval_value = 'custom_interval_value';

    const string custom_interval_unit = 'custom_interval_unit';

    const string ends_mode = 'ends_mode';

    const string ends_date = 'ends_date';

    const string extended_date = 'extended_date';

    const string extended_interval = 'extended_interval';

    const string custom_extended_interval_value = 'custom_extended_interval_value';

    const string custom_extended_interval_unit = 'custom_extended_interval_unit';

    const string insurance_id = 'insurance_id';

    const string next_booking_date = 'next_booking_date';

    const string created_at = Model::CREATED_AT;

    const string updated_at = Model::UPDATED_AT;

    // relations
    const string has_many_documents = 'documents';

    const string has_many_transactions = 'transactions';

    const string has_many_matching_suggestions = 'matchingSuggestions';

    const string has_many_matching_rules = 'matchingRules';

    const string has_many_booking_date_suggestions = 'bookingDateSuggestions';

    const string belongs_to_many_transaction_categories = 'transactionCategories';

    const string has_many_reminders = 'reminders';

    const string belongs_to_user = 'user';

    const string belongs_to_insurance = 'insurance';

    const string belongs_to_category = 'category';

    const string morph_to_many_tags = 'tags';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::name,
        self::notes,
        self::amount,
        self::category_id,
        self::include_subcategories,
        self::interval,
        self::custom_interval_value,
        self::custom_interval_unit,
        self::ends_mode,
        self::ends_date,
        self::extended_date,
        self::extended_interval,
        self::custom_extended_interval_value,
        self::custom_extended_interval_unit,
        self::insurance_id,
        self::next_booking_date,
    ];

    protected $casts = [
        self::amount => 'decimal:2',
        self::include_subcategories => 'boolean',
        self::custom_interval_value => 'integer',
        self::ends_date => 'date',
        self::extended_date => 'date',
        self::custom_extended_interval_value => 'integer',
        self::next_booking_date => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FixedCostCategory::class, self::category_id);
    }

    public function documents(): MorphToMany
    {
        return $this->morphToMany(
            Document::class,
            Document::morph_to_documentable,
            Documentables::TABLE,
            Document::documentable_id,
            Document::documentable_document_id,
        )
            ->orderBy(Document::sort, 'asc')
            ->orderBy(Document::id, 'asc');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, Transaction::fixed_cost_id);
    }

    public function matchingSuggestions(): HasMany
    {
        return $this->hasMany(TransactionMatchingSuggestion::class, TransactionMatchingSuggestion::fixed_cost_id);
    }

    public function matchingRules(): HasMany
    {
        return $this->hasMany(FixedCostMatchingRule::class, FixedCostMatchingRule::fixed_cost_id);
    }

    public function bookingDateSuggestions(): HasMany
    {
        return $this->hasMany(
            FixedCostBookingDateSuggestion::class,
            FixedCostBookingDateSuggestion::fixed_cost_id,
        );
    }

    public function transactionCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            TransactionCategory::class,
            FinancialFixedCostTransactionCategory::PIVOT_TABLE,
            FinancialFixedCostTransactionCategory::FIXED_COST_ID,
            FinancialFixedCostTransactionCategory::CATEGORY_ID,
        );
    }

    /**
     * IDs der direkt verknüpften Kategorien, ohne Unterkategorien.
     *
     * @return list<int>
     */
    public function getDirectCategoryIds(): array
    {
        $categories = $this->relationLoaded(self::belongs_to_many_transaction_categories)
            ? $this->transactionCategories
            : $this->transactionCategories()->get();

        return $categories
            ->map(static fn(TransactionCategory $category): int => (int)$category->getKey())
            ->values()
            ->all();
    }

    /**
     * Alle Kategorie-IDs, die diese Fixkosten-Position beim Transaktions-Matching berücksichtigt.
     * Bei include_subcategories werden die Unterkategorien der verknüpften Kategorien ergänzt.
     *
     * @return list<int>
     */
    public function getMatchingCategoryIds(): array
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
}
