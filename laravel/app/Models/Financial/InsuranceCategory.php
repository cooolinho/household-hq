<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $group
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Insurance[] $insurances
 */
class InsuranceCategory extends Model
{
    const string TABLE = 'financial_insurance_categories';
    const string GROUP_NOT_CATEGORIZED = 'Nicht kategorisiert';

    const string id = 'id';
    const string name = 'name';
    const string group = 'group';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    const string has_many_insurances = 'insurances';

    protected $table = self::TABLE;

    protected $fillable = [
        self::name,
        self::group,
    ];

    public function insurances(): HasMany
    {
        return $this->hasMany(Insurance::class, 'category_id')
            ->orderBy(Insurance::name, 'asc');
    }
}

