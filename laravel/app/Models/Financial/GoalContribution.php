<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class GoalContribution
 *
 * Manuelle Einzahlung/Tilgung auf ein Ziel, z. B. beiseitegelegtes Bargeld.
 * Bewusst kein Bezug zu Transaction — der Betrag stammt nicht aus einem Bankkonto.
 *
 * Columns
 *
 * @property int $id
 * @property int $goal_id
 * @property Carbon $date
 * @property float $amount
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property Goal $goal
 */
class GoalContribution extends Model
{
    const string TABLE = 'financial_goal_contributions';

    // columns
    const string id = 'id';

    const string goal_id = 'goal_id';

    const string date = 'date';

    const string amount = 'amount';

    const string note = 'note';

    const string created_at = Model::CREATED_AT;

    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_goal = 'goal';

    protected $table = self::TABLE;

    protected $fillable = [
        self::goal_id,
        self::date,
        self::amount,
        self::note,
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class, self::goal_id);
    }

    protected function casts(): array
    {
        return [
            self::date => 'date',
            self::amount => 'float',
        ];
    }
}
