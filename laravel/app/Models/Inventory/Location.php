<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Location
 *
 * Columns
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $preview_image
 * @property int|null $collection_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property Collection|null $collection
 * @property Article[]|null $articles
 */
class Location extends Model
{
    const string TABLE = 'inventory_locations';

    const string id = 'id';
    const string name = 'name';
    const string description = 'description';
    const string preview_image = 'preview_image';
    const string collection_id = 'collection_id';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string has_many_articles = 'articles';
    const string belongs_to_collection = 'collection';

    protected $table = self::TABLE;
    protected $fillable = [
        self::name,
        self::description,
        self::preview_image,
        self::collection_id,
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, self::collection_id);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, Article::location_id);
    }
}
