<?php

namespace App\Models\EnergyTracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Historical unit price for a measurement device contract.
 *
 * @property int $id
 * @property int $measurement_device_contract_id
 * @property string $unit_price
 * @property Carbon $valid_from
 * @property string|null $notes
 */
class MeasurementDeviceContractPrice extends Model
{
    const string TABLE = 'energy_tracker_measurement_device_contract_prices';

    // columns
    const string id = 'id';

    const string measurement_device_contract_id = 'measurement_device_contract_id';

    const string unit_price = 'unit_price';

    const string valid_from = 'valid_from';

    const string notes = 'notes';

    const string created_at = Model::CREATED_AT;

    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_contract = 'contract';

    protected $table = self::TABLE;

    protected $fillable = [
        self::measurement_device_contract_id,
        self::unit_price,
        self::valid_from,
        self::notes,
    ];

    protected $casts = [
        self::unit_price => 'decimal:6',
        self::valid_from => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $price): void {
            if ($price->{self::unit_price} !== null && (float)$price->{self::unit_price} < 0) {
                throw new InvalidArgumentException('Der Preis pro Einheit darf nicht negativ sein.');
            }
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(
            MeasurementDeviceContract::class,
            self::measurement_device_contract_id,
        );
    }
}
