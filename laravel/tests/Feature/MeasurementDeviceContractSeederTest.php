<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\EnergyTracker\MeasurementDeviceContractPrice;
use App\Models\Enums\EnergyTrackerCountingMethodEnum;
use App\Models\Enums\EnergyTrackerCountingTypeEnum;
use App\Models\Enums\EnergyTrackerUnitEnum;
use App\Models\User;
use Database\Seeders\MeasurementDeviceContractSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementDeviceContractSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_demo_contracts_prices_and_contacts_idempotently(): void
    {
        $user = User::factory()->create([
            User::email => UserSeeder::ADMIN_EMAIL,
        ]);
        $this->createDevice($user, 'Electricity', EnergyTrackerCountingTypeEnum::STROM->name, EnergyTrackerUnitEnum::KWH->name);
        $this->createDevice($user, 'Gas', EnergyTrackerCountingTypeEnum::GAS->name, EnergyTrackerUnitEnum::M3->name);

        $this->seed(MeasurementDeviceContractSeeder::class);
        $this->seed(MeasurementDeviceContractSeeder::class);

        $this->assertSame(3, MeasurementDeviceContract::query()->count());
        $this->assertSame(7, MeasurementDeviceContractPrice::query()->count());
        $this->assertSame(2, ContactPerson::query()->where(ContactPerson::user_id, $user->getKey())->count());
        $this->assertSame(1, MeasurementDeviceContract::query()
            ->where(MeasurementDeviceContract::is_active, true)
            ->whereHas(
                MeasurementDeviceContract::belongs_to_measurement_device,
                fn($query) => $query
                    ->where(MeasurementDevice::user_id, $user->getKey())
                    ->where(MeasurementDevice::name, 'Electricity'),
            )
            ->count());
        $this->assertSame(1, MeasurementDeviceContract::query()
            ->where(MeasurementDeviceContract::is_active, true)
            ->whereHas(
                MeasurementDeviceContract::belongs_to_measurement_device,
                fn($query) => $query
                    ->where(MeasurementDevice::user_id, $user->getKey())
                    ->where(MeasurementDevice::name, 'Gas'),
            )
            ->count());
        $this->assertDatabaseHas(ContactPerson::TABLE, [
            ContactPerson::email => 'anna.mueller@example.com',
            ContactPerson::type => 'PRIVATE',
        ]);
        $this->assertDatabaseHas(ContactPerson::TABLE, [
            ContactPerson::email => 'markus.schneider@stadtwerke.example',
            ContactPerson::type => 'BUSINESS',
        ]);
    }

    private function createDevice(
        User   $user,
        string $name,
        string $countingType,
        string $countingUnit,
    ): MeasurementDevice
    {
        return MeasurementDevice::forceCreate([
            MeasurementDevice::user_id => $user->getKey(),
            MeasurementDevice::name => $name,
            MeasurementDevice::group => '@Home',
            MeasurementDevice::counting_type => $countingType,
            MeasurementDevice::counting_method => EnergyTrackerCountingMethodEnum::ASCENDING->name,
            MeasurementDevice::counting_unit => $countingUnit,
            MeasurementDevice::meter_reading_value => '1000',
            MeasurementDevice::meter_reading_date => now()->subMonths(6)->startOfMonth(),
            MeasurementDevice::decimal_places => 2,
        ]);
    }
}
