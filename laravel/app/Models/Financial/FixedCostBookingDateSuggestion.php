<?php

namespace App\Models\Financial;

use App\Models\Enums\MatchingSuggestionStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class FixedCostBookingDateSuggestion
 *
 * Columns
 * @property int $id
 * @property int $fixed_cost_id
 * @property int $suggested_day
 * @property Carbon $suggested_date
 * @property Carbon|null $current_date
 * @property int $deviation_days
 * @property int $sample_count
 * @property Carbon $analyzed_from
 * @property Carbon $analyzed_to
 * @property string $status  (PENDING|ACCEPTED|REJECTED)
 *
 * Relations
 * @property FixedCost $fixedCost
 */
class FixedCostBookingDateSuggestion extends Model
{
    const string TABLE = 'financial_fixed_cost_booking_date_suggestions';

    // columns
    const string id = 'id';
    const string fixed_cost_id = 'fixed_cost_id';
    const string suggested_day = 'suggested_day';
    const string suggested_date = 'suggested_date';
    const string current_date = 'current_date';
    const string deviation_days = 'deviation_days';
    const string sample_count = 'sample_count';
    const string analyzed_from = 'analyzed_from';
    const string analyzed_to = 'analyzed_to';
    const string status = 'status';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_fixed_cost = 'fixedCost';

    protected $table = self::TABLE;

    protected $fillable = [
        self::fixed_cost_id,
        self::suggested_day,
        self::suggested_date,
        self::current_date,
        self::deviation_days,
        self::sample_count,
        self::analyzed_from,
        self::analyzed_to,
        self::status,
    ];

    protected $casts = [
        self::suggested_day => 'integer',
        self::suggested_date => 'date',
        self::current_date => 'date',
        self::deviation_days => 'integer',
        self::sample_count => 'integer',
        self::analyzed_from => 'date',
        self::analyzed_to => 'date',
    ];

    public function fixedCost(): BelongsTo
    {
        return $this->belongsTo(FixedCost::class);
    }

    public function isPending(): bool
    {
        return $this->status === MatchingSuggestionStatusEnum::PENDING->name;
    }
}
