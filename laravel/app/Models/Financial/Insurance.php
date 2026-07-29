<?php

namespace App\Models\Financial;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Tags\HasTags;

/**
 * Class Insurance
 *
 * Columns
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $number
 * @property string|null $type
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $company
 * @property string|null $contact_person
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $address_zip
 * @property string|null $address_city
 * @property string|null $address_country
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property User $user
 * @property Collection|Document[] $has_many_documents
 * @property Collection|FixedCost[] $has_many_fixed_costs
 */
class Insurance extends Model
{
    use HasTags;

    const string TABLE = 'financial_insurances';

    // columns
    const string id = 'id';
    const string name = 'name';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    const string number = 'number';
    const string type = 'type';
    const string start_date = 'start_date';
    const string end_date = 'end_date';
    const string company = 'company';
    const string contact_person = 'contact_person';
    const string phone = 'phone';
    const string email = 'email';

    // address columns
    const string address_line_1 = 'address_line_1';
    const string address_line_2 = 'address_line_2';
    const string address_zip = 'address_zip';
    const string address_city = 'address_city';
    const string address_country = 'address_country';

    // relations
    const string user_id = 'user_id';

    // relation method names
    const string has_many_documents = 'documents';
    const string has_many_fixed_costs = 'fixedCosts';
    const string belongs_to_user = 'user';
    const string morph_to_many_tags = 'tags';

    protected $table = self::TABLE;
    protected $fillable = [
        self::name,
        self::user_id,
        self::number,
        self::type,
        self::start_date,
        self::end_date,
        self::company,
        self::contact_person,
        self::phone,
        self::email,
        self::address_line_1,
        self::address_line_2,
        self::address_zip,
        self::address_city,
        self::address_country,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, Document::morph_to_documentable)
            ->orderBy(Document::sort, 'asc')
            ->orderBy(Document::id, 'asc');
    }

    public function fixedCosts(): HasMany
    {
        return $this->hasMany(FixedCost::class, FixedCost::insurance_id)
            ->orderBy(FixedCost::name, 'asc');
    }
}
