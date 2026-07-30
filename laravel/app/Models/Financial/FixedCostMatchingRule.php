<?php

namespace App\Models\Financial;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lernregel für die Zuordnung von Transaktionen zu Fixkosten.
 */
class FixedCostMatchingRule extends Model
{
    const string TABLE = 'financial_fixed_cost_matching_rules';

    // columns
    const string id = 'id';
    const string user_id = 'user_id';
    const string fixed_cost_id = 'fixed_cost_id';
    const string fingerprint = 'fingerprint';
    const string payer_token = 'payer_token';
    const string purpose_token = 'purpose_token';
    const string amount_sign = 'amount_sign';
    const string amount_min = 'amount_min';
    const string amount_max = 'amount_max';
    const string positive_weight = 'positive_weight';
    const string negative_weight = 'negative_weight';
    const string last_source = 'last_source';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // values
    const string SOURCE_AUTO_LINK = 'AUTO_LINK';
    const string SOURCE_SUGGESTION_ACCEPT = 'SUGGESTION_ACCEPT';
    const string SOURCE_SUGGESTION_REJECT = 'SUGGESTION_REJECT';

    // relations
    const string belongs_to_user = 'user';
    const string belongs_to_fixed_cost = 'fixedCost';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::fixed_cost_id,
        self::fingerprint,
        self::payer_token,
        self::purpose_token,
        self::amount_sign,
        self::amount_min,
        self::amount_max,
        self::positive_weight,
        self::negative_weight,
        self::last_source,
    ];

    protected $casts = [
        self::amount_sign => 'integer',
        self::amount_min => 'float',
        self::amount_max => 'float',
        self::positive_weight => 'float',
        self::negative_weight => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fixedCost(): BelongsTo
    {
        return $this->belongsTo(FixedCost::class, self::fixed_cost_id);
    }
}

