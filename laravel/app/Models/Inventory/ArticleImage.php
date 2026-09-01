<?php

namespace App\Models\Inventory;

use App\AppConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class ArticleImage
 *
 * Columns
 * @property int $id
 * @property int $article_id
 * @property string $path
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property Article|null $article
 */
class ArticleImage extends Model
{
    const string TABLE = 'inventory_article_images';
    const string STORAGE_DISK = AppConfig::FILESYSTEM_INVENTORY_ARTICLE_IMAGES;

    const string id = 'id';
    const string article_id = 'article_id';
    const string path = 'path';
    const string sort = 'sort';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    const string belongs_to_article = 'article';

    protected $table = self::TABLE;

    protected $fillable = [
        self::article_id,
        self::path,
        self::sort,
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, self::article_id);
    }
}
