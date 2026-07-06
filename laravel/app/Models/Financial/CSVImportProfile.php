<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class CSVImportProfile
 *
 * Columns
 * @property int $id
 * @property string $name
 * @property string|null $bank
 * @property string $delimiter
 * @property string $enclosure
 * @property string $escape
 * @property array|null $mapping
 * @property int $offset_header
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CSVImportProfile extends Model
{
    const string TABLE = 'financial_csv_import_profiles';

    const string id = 'id';
    const string name = 'name';
    const string bank = 'bank';
    const string delimiter = 'delimiter';
    const string enclosure = 'enclosure';
    const string escape = 'escape';
    const string mapping = 'mapping';
    const string offset_header = 'offset_header';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    protected $table = self::TABLE;

    protected $fillable = [
        self::name,
        self::bank,
        self::delimiter,
        self::enclosure,
        self::escape,
        self::mapping,
        self::offset_header,
    ];

    protected $casts = [
        self::mapping => 'array',
    ];
}
