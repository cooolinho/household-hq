<?php

namespace App\Models\EnergyTracker;

use App\Models\ContactPerson;
use App\Models\Concerns\HasReminders;
use App\Models\Contracts\Documentables;
use App\Models\Document;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Contract and tariff configuration for a measurement device.
 *
 * @property int $id
 * @property int $measurement_device_id
 * @property string $name
 * @property string|null $provider
 * @property string|null $contract_number
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property string|null $base_price
 * @property string|null $base_price_interval
 * @property string $currency
 * @property bool $is_active
 * @property string|null $notes
 */
class MeasurementDeviceContract extends Model
{
    use HasReminders;

    const string TABLE = 'energy_tracker_measurement_device_contracts';

    // columns
    const string id = 'id';

    const string measurement_device_id = 'measurement_device_id';

    const string name = 'name';

    const string provider = 'provider';

    const string contract_number = 'contract_number';

    const string starts_on = 'starts_on';

    const string ends_on = 'ends_on';

    const string base_price = 'base_price';

    const string base_price_interval = 'base_price_interval';

    const string currency = 'currency';

    const string is_active = 'is_active';

    const string active_measurement_device_id = 'active_measurement_device_id';

    const string notes = 'notes';

    const string created_at = Model::CREATED_AT;

    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_measurement_device = 'measurementDevice';

    const string has_many_prices = 'prices';

    const string has_many_documents = 'documents';

    const string has_many_reminders = 'reminders';

    const string belongs_to_many_contacts = 'contacts';

    protected $table = self::TABLE;

    protected $fillable = [
        self::measurement_device_id,
        self::name,
        self::provider,
        self::contract_number,
        self::starts_on,
        self::ends_on,
        self::base_price,
        self::base_price_interval,
        self::currency,
        self::is_active,
        self::notes,
    ];

    protected $casts = [
        self::base_price => 'decimal:4',
        self::starts_on => 'date',
        self::ends_on => 'date',
        self::is_active => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $contract): void {
            if (!$contract->is_active || !$contract->measurement_device_id) {
                return;
            }

            $query = self::query()
                ->where(self::measurement_device_id, $contract->measurement_device_id)
                ->where(self::is_active, true);

            if ($contract->exists) {
                $query->whereKeyNot($contract->getKey());
            }

            $query->update([self::is_active => false]);
        });

        static::deleting(function (self $contract): void {
            $contract->documents()->detach();
        });
    }

    public function documents(): MorphToMany
    {
        return $this->morphToMany(
            Document::class,
            Document::morph_to_documentable,
            Documentables::TABLE,
            Documentables::documentable_id,
            Documentables::document_id,
        )
            ->orderBy(Document::sort)
            ->orderBy(Document::id);
    }

    public function measurementDevice(): BelongsTo
    {
        return $this->belongsTo(MeasurementDevice::class, self::measurement_device_id);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            ContactPerson::class,
            MeasurementDeviceContractContact::TABLE,
            MeasurementDeviceContractContact::measurement_device_contract_id,
            MeasurementDeviceContractContact::contact_person_id,
        )
            ->orderBy(ContactPerson::firstname)
            ->orderBy(ContactPerson::lastname);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(self::is_active, true);
    }

    public function isActive(): bool
    {
        return (bool)$this->getAttribute(self::is_active);
    }

    public function activate(): bool
    {
        return DB::transaction(function (): bool {
            $this->setAttribute(self::is_active, true);

            return $this->save();
        });
    }

    public function deactivate(): bool
    {
        $this->setAttribute(self::is_active, false);

        return $this->save();
    }

    public function currentPrice(CarbonInterface|string|null $date = null): ?MeasurementDeviceContractPrice
    {
        $date = $date instanceof CarbonInterface
            ? $date
            : CarbonImmutable::parse($date ?? now());

        if ($this->relationLoaded(self::has_many_prices)) {
            return $this->prices
                ->filter(fn(MeasurementDeviceContractPrice $price): bool => $price->{MeasurementDeviceContractPrice::valid_from}
                    && $price->{MeasurementDeviceContractPrice::valid_from}->lessThanOrEqualTo($date))
                ->sortByDesc(MeasurementDeviceContractPrice::valid_from)
                ->first();
        }

        return $this->prices()
            ->where(MeasurementDeviceContractPrice::valid_from, '<=', $date->toDateString())
            ->reorder()
            ->orderByDesc(MeasurementDeviceContractPrice::valid_from)
            ->orderByDesc(MeasurementDeviceContractPrice::id)
            ->first();
    }

    public function prices(): HasMany
    {
        return $this->hasMany(MeasurementDeviceContractPrice::class, MeasurementDeviceContractPrice::measurement_device_contract_id)
            ->orderBy(MeasurementDeviceContractPrice::valid_from)
            ->orderBy(MeasurementDeviceContractPrice::id);
    }

    public function monthlyBasePrice(): float
    {
        $basePrice = (float)($this->getAttribute(self::base_price) ?? 0);

        return match ($this->getAttribute(self::base_price_interval)) {
            'YEARLY' => $basePrice / 12,
            default => $basePrice,
        };
    }

    public function annualBasePrice(): float
    {
        $basePrice = (float)($this->getAttribute(self::base_price) ?? 0);

        return match ($this->getAttribute(self::base_price_interval)) {
            'YEARLY' => $basePrice,
            default => $basePrice * 12,
        };
    }
}
