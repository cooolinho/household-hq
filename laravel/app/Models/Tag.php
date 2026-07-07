<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Tags\Tag as SpatieTag;

/**
 * @property int $id
 * @property int $user_id
 * @property array $name
 * @property array $slug
 * @property string|null $type
 * @property int|null $order_column
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Tag extends SpatieTag
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;
    const string TABLE = 'tags';

    // columns
    const string id = 'id';
    const string user_id = 'user_id';
    const string name = 'name';
    const string slug = 'slug';
    const string type = 'type';
    const string order_column = 'order_column';
    const string created_at = self::CREATED_AT;
    const string updated_at = self::UPDATED_AT;

    // relations
    const string belongs_to_user = 'user';

    protected $table = self::TABLE;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }
}
