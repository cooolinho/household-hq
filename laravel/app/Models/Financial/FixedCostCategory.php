<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $group
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property Collection|FixedCost[] $fixedCosts
 */
class FixedCostCategory extends Model
{
    const string TABLE = 'financial_fixed_cost_categories';

    const string id = 'id';
    const string name = 'name';
    const string group = 'group';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    const string has_many_fixed_costs = 'fixedCosts';

    protected $table = self::TABLE;

    protected $fillable = [
        self::name,
        self::group,
    ];

    public function fixedCosts(): HasMany
    {
        return $this->hasMany(FixedCost::class, FixedCost::category_id);
    }
}

