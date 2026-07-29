<?php

namespace App\Services\EnergyTracker;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\Enums\EnergyTrackerCountingMethodEnum;

class ReadingEntryValueGuard
{
    public static function defaultValue(MeasurementDevice $device): ?float
    {
        return self::referenceValue($device);
    }

    public static function referenceValue(MeasurementDevice $device): ?float
    {
        $lastReadingValue = $device->lastReadingEntry()?->reading_value;

        if ($lastReadingValue !== null) {
            return (float)$lastReadingValue;
        }

        if ($device->meter_reading_value !== null) {
            return (float)$device->meter_reading_value;
        }

        return null;
    }

    public static function validateValue(MeasurementDevice $device, float $value): ?string
    {
        $referenceValue = self::referenceValue($device);

        if ($referenceValue === null) {
            return null;
        }

        $countingMethod = strtoupper((string)$device->counting_method);

        if (
            $countingMethod === EnergyTrackerCountingMethodEnum::ASCENDING->name
            && $value < $referenceValue
        ) {
            return sprintf(
                'Bei aufsteigender Zaehlmethode muss der Wert groesser oder gleich %.4f sein.',
                $referenceValue,
            );
        }

        if (
            $countingMethod === EnergyTrackerCountingMethodEnum::DESCENDING->name
            && $value > $referenceValue
        ) {
            return sprintf(
                'Bei absteigender Zaehlmethode muss der Wert kleiner oder gleich %.4f sein.',
                $referenceValue,
            );
        }

        return null;
    }
}

