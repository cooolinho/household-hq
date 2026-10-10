<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class CSVImportProfile
 *
 * Import-Profile sind haushaltsweit (keine user_id) und werden Bankkonten über
 * BankAccount::csv_profile_id zugewiesen.
 *
 * Columns
 *
 * @property int $id
 * @property string $name
 * @property string|null $bank
 * @property string $delimiter
 * @property string $enclosure
 * @property string $escape
 * @property string|null $encoding
 * @property bool|null $has_header
 * @property string $amount_format
 * @property string|null $date_format
 * @property array<string, int|string|null>|null $mapping Transaction-Feld => 0-basierter Spaltenindex
 * @property array<string, string|null>|null $header_mapping Transaction-Feld => CSV-Spaltenname
 * @property array<int, string>|null $header_columns alle CSV-Spaltennamen beim Speichern des Profils
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

    const string encoding = 'encoding';

    const string has_header = 'has_header';

    const string amount_format = 'amount_format';

    const string date_format = 'date_format';

    const string mapping = 'mapping';

    const string header_mapping = 'header_mapping';

    const string header_columns = 'header_columns';

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
        self::encoding,
        self::has_header,
        self::amount_format,
        self::date_format,
        self::mapping,
        self::header_mapping,
        self::header_columns,
        self::offset_header,
    ];

    protected $casts = [
        self::mapping => 'array',
        self::header_mapping => 'array',
        self::header_columns => 'array',
        self::has_header => 'boolean',
        self::offset_header => 'integer',
    ];
}
