<?php

namespace Database\Seeders;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\ReadingEntry;
use App\Models\Enums\EnergyTrackerCountingMethodEnum;
use App\Models\Enums\EnergyTrackerCountingTypeEnum;
use App\Models\Enums\EnergyTrackerUnitEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnergyTrackerSeeder extends Seeder
{
    const int SUB_MONTHS = 6;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = UserSeeder::getAdminUser();

        self::createElectricityMeasurementDevice($user);
        self::createGasMeasurementDevice($user);
    }

    private static function createElectricityMeasurementDevice(User $user): void
    {
        self::createMeasurementDevice(
            $user,
            'Electricity',
            EnergyTrackerCountingTypeEnum::STROM->name,
            EnergyTrackerUnitEnum::KWH->name,
            'Main meter for electricity usage.'
        );
    }

    private static function createMeasurementDevice(User $user, string $name, string $countingType, string $countingUnit, string $description): void
    {
        $measurementDevice = MeasurementDevice::query()
            ->create([
                MeasurementDevice::user_id => $user->id,
                MeasurementDevice::name => $name,
                MeasurementDevice::group => '@Home',
                MeasurementDevice::counting_type => $countingType,
                MeasurementDevice::counting_method => EnergyTrackerCountingMethodEnum::ASCENDING->name,
                MeasurementDevice::counting_unit => $countingUnit,
                MeasurementDevice::meter_reading_date => now()->subMonths(self::SUB_MONTHS)->startOfMonth(),
                MeasurementDevice::meter_reading_value => fake()->numberBetween(1000, 5000),
                MeasurementDevice::meter_description => $description,
                MeasurementDevice::decimal_places => 2,
            ]);

        $startValue = $measurementDevice->meter_reading_value;
        $startDate = $measurementDevice->meter_reading_date;

        for ($i = 1; $i <= self::SUB_MONTHS; $i++) {
            $readingValue = $startValue + ($i * 100) + fake()->numberBetween(100, 300);

            // Increment by 30 days for each entry
            $readingDate = $startDate->copy()->addMonths($i)->startOfMonth();

            ReadingEntry::query()
                ->create([
                    ReadingEntry::measurement_device_id => $measurementDevice->id,
                    ReadingEntry::reading_value => $readingValue,
                    ReadingEntry::reading_date => $readingDate,
                ]);
        }
    }

    private static function createGasMeasurementDevice(User $user): void
    {
        self::createMeasurementDevice(
            $user,
            'Gas',
            EnergyTrackerCountingTypeEnum::GAS->name,
            EnergyTrackerUnitEnum::M3->name,
            'Main meter for gas usage.'
        );
    }

}
