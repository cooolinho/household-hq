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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementDeviceContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_keeps_only_one_active_contract_per_measurement_device(): void
    {
        $user = User::factory()->create();
        $device = $this->createDevice($user);

        $first = $this->createContract($device, 'Erster Vertrag', true);
        $second = $this->createContract($device, 'Nachfolge-Vertrag', true);

        $this->assertFalse($first->fresh()->isActive());
        $this->assertTrue($second->fresh()->isActive());
        $this->assertSame($second->getKey(), $device->activeContract()->value(MeasurementDeviceContract::id));
    }

    private function createDevice(User $user): MeasurementDevice
    {
        return MeasurementDevice::forceCreate([
            MeasurementDevice::user_id => $user->getKey(),
            MeasurementDevice::name => 'Testmessgerät',
            MeasurementDevice::counting_type => EnergyTrackerCountingTypeEnum::STROM->name,
            MeasurementDevice::counting_method => EnergyTrackerCountingMethodEnum::ASCENDING->name,
            MeasurementDevice::counting_unit => EnergyTrackerUnitEnum::KWH->name,
            MeasurementDevice::meter_reading_value => '0',
            MeasurementDevice::meter_reading_date => now(),
            MeasurementDevice::decimal_places => 4,
        ]);
    }

    private function createContract(
        MeasurementDevice $device,
        string            $name,
        bool              $active,
    ): MeasurementDeviceContract
    {
        return $device->contracts()->create([
            MeasurementDeviceContract::name => $name,
            MeasurementDeviceContract::is_active => $active,
            MeasurementDeviceContract::currency => 'EUR',
        ]);
    }

    public function test_it_supports_price_history_and_contact_assignments(): void
    {
        $user = User::factory()->create();
        $device = $this->createDevice($user);
        $contract = $this->createContract($device, 'Stromvertrag', true);
        $contact = ContactPerson::factory()->forUser($user->getKey())->create();

        $contract->prices()->create([
            MeasurementDeviceContractPrice::unit_price => 0.32,
            MeasurementDeviceContractPrice::valid_from => '2026-01-01',
        ]);
        $contract->prices()->create([
            MeasurementDeviceContractPrice::unit_price => 0.35,
            MeasurementDeviceContractPrice::valid_from => '2026-07-01',
        ]);
        $contract->contacts()->attach($contact);

        $this->assertCount(2, $contract->prices()->get());
        $this->assertSame(0.35, (float)$contract->fresh()->currentPrice('2026-08-01')->unit_price);
        $this->assertTrue($contract->contacts()->whereKey($contact)->exists());
    }
}
