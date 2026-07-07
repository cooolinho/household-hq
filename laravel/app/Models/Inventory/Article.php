<?php

namespace App\Models\Inventory;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;


/**
 * Class Article
 *
 * Columns
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $amount
 * @property string|null $serial_number
 * @property string|null $model_number
 * @property string|null $manufacturer
 * @property string|null $notes
 * @property bool|null $insured
 * @property bool|null $archived
 * @property int|null $parent_article_id
 * @property int|null $location_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property Location|null $location
 * @property Article|null $parent
 * @property Document[]|null $documents
 */
class Article extends Model
{
    const string TABLE = 'inventory_articles';

    // columns
    const string id = 'id';
    const string name = 'name';
    const string description = 'description';
    const string amount = 'amount';
    const string serial_number = 'serial_number';
    const string model_number = 'model_number';
    const string manufacturer = 'manufacturer';
    const string notes = 'notes';
    const string insured = 'insured';
    const string archived = 'archived';
    const string purchase_price = 'purchase_price';
    const string purchase_place = 'purchase_place';
    const string purchase_date = 'purchase_date';
    const string warranty_lifetime = 'warranty_lifetime';
    const string warranty_until = 'warranty_until';
    const string warranty_details = 'warranty_details';
    const string sale_price = 'sale_price';
    const string sold_to = 'sold_to';
    const string sold_at = 'sold_at';
    const string parent_id = 'parent_id';
    const string location_id = 'location_id';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_parent = 'parent';
    const string belongs_to_location = 'location';
    const string has_many_documents = 'documents';

    protected $table = self::TABLE;
    protected $fillable = [
        self::name,
        self::description,
        self::amount,
        self::serial_number,
        self::model_number,
        self::manufacturer,
        self::notes,
        self::insured,
        self::archived,
        self::parent_id,
        self::location_id,
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Article::class, self::parent_id);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, self::location_id);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, Document::morph_to_documentable)
            ->orderBy(Document::sort, 'asc')
            ->orderBy(Document::id, 'asc');
    }
}
