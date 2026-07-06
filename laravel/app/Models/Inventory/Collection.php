<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Collection
 *
 * Columns
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property User $user
 * @property Location[]|null $locations
 */
class Collection extends Model
{
    const string TABLE = 'inventory_collections';

    const string id = 'id';
    const string user_id = 'user_id';
    const string name = 'name';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_user = 'user';
    const string has_many_locations = 'locations';
    const string has_many_articles = 'articles';

    protected $table = self::TABLE;
    protected $fillable = [
        self::name,
        self::user_id,
    ];

    public function locations()
    {
        return $this->hasMany(Location::class, Location::collection_id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }
}
