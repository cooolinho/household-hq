<?php

namespace App\Models\EnergyTracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Tags\HasTags;

/**
 * Class ReadingEntry
 *
 * Columns
 * @property int $id
 * @property int $measurement_device_id
 * @property float $reading_value
 * @property string $reading_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property MeasurementDevice $measurementDevice
 */
class ReadingEntry extends Model
{
    use HasTags;

    const string TABLE = 'energy_tracker_reading_entries';

    // columns
    const string id = 'id';
    const string measurement_device_id = 'measurement_device_id';
    const string reading_value = 'reading_value';
    const string reading_date = 'reading_date';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_measurement_device = 'measurementDevice';
    const string morph_to_many_tags = 'tags';

    protected $table = self::TABLE;

    protected $fillable = [
        self::measurement_device_id,
        self::reading_value,
        self::reading_date,
    ];

    protected $casts = [
        self::reading_date => 'datetime',
    ];

    public function measurementDevice(): BelongsTo
    {
        return $this->belongsTo(MeasurementDevice::class, self::measurement_device_id);
    }
}
