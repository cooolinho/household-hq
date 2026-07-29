<?php

namespace Tests\Unit;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\ReadingEntry;
use App\Models\Enums\EnergyTrackerCountingMethodEnum;
use App\Models\Enums\EnergyTrackerCountingTypeEnum;
use App\Services\EnergyTracker\ReadingEntryValueGuard;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class EnergyTrackerReadingEntryValueGuardTest extends TestCase
{
    public function test_it_rejects_smaller_values_for_ascending_counters(): void
    {
        $device = $this->makeDeviceWithReferenceValue(100.0, EnergyTrackerCountingMethodEnum::ASCENDING->name);

        $error = ReadingEntryValueGuard::validateValue($device, 99.0);

        $this->assertNotNull($error);
    }

    private function makeDeviceWithReferenceValue(float $referenceValue, string $countingMethod): MeasurementDevice
    {
        $lastReading = new ReadingEntry();
        $lastReading->forceFill([
            ReadingEntry::reading_value => $referenceValue,
        ]);

        $device = new class extends MeasurementDevice {
            public ?ReadingEntry $lastReadingEntryForTest = null;

            public function lastReadingEntry(): ReadingEntry|Model|null
            {
                return $this->lastReadingEntryForTest;
            }
        };

        $device->forceFill([
            MeasurementDevice::counting_method => $countingMethod,
            MeasurementDevice::meter_reading_value => 50.0,
        ]);
        $device->lastReadingEntryForTest = $lastReading;

        return $device;
    }

    public function test_it_rejects_larger_values_for_descending_counters(): void
    {
        $device = $this->makeDeviceWithReferenceValue(100.0, EnergyTrackerCountingMethodEnum::DESCENDING->name);

        $error = ReadingEntryValueGuard::validateValue($device, 101.0);

        $this->assertNotNull($error);
    }

    public function test_it_accepts_any_value_for_fluctuating_counters(): void
    {
        $device = $this->makeDeviceWithReferenceValue(100.0, EnergyTrackerCountingMethodEnum::FLUCTUATING->name);

        $error = ReadingEntryValueGuard::validateValue($device, 1.0);

        $this->assertNull($error);
    }

    public function test_counting_type_icon_mapping_is_available(): void
    {
        $this->assertSame('heroicon-o-bolt', EnergyTrackerCountingTypeEnum::STROM->icon());
        $this->assertSame('heroicon-o-fire', EnergyTrackerCountingTypeEnum::GAS->icon());
    }
}

