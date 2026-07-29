<?php

namespace App\Models\EnergyTracker;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Tags\HasTags;

/**
 * Class MeasurementDevice
 *
 * Columns
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $group
 * @property string|null $counting_type
 * @property string|null $counting_method
 * @property string|null $counting_unit
 * @property float|null $meter_reading_value
 * @property Carbon|null $meter_reading_date
 * @property string|null $meter_description
 * @property int|null $decimal_places
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property User|null $user
 * @property ReadingEntry[]|null $readingEntries
 */
class MeasurementDevice extends Model
{
    use HasTags;

    const string TABLE = 'energy_tracker_measurement_devices';

    // columns
    const string id = 'id';
    const string user_id = 'user_id';
    const string name = 'name';
    const string group = 'group';
    const string counting_type = 'counting_type';
    const string counting_method = 'counting_method';
    const string counting_unit = 'counting_unit';
    const string meter_reading_value = 'meter_reading_value';
    const string meter_reading_date = 'meter_reading_date';
    const string meter_description = 'meter_description';
    const string decimal_places = 'decimal_places';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_user = 'user';
    const string has_many_reading_entries = 'readingEntries';
    const string morph_to_many_tags = 'tags';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::name,
        self::group,
        self::counting_type,
        self::counting_method,
        self::counting_unit,
        self::meter_reading_value,
        self::meter_reading_date,
        self::meter_description,
        self::decimal_places,
    ];

    protected $casts = [
        self::meter_reading_date => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    public function lastReadingEntry(): ReadingEntry|Model|null
    {
        return $this->readingEntries()
            ->orderByDesc(ReadingEntry::reading_date)
            ->first();
    }

    public function readingEntries(): HasMany
    {
        return $this->hasMany(ReadingEntry::class, ReadingEntry::measurement_device_id);
    }
}
