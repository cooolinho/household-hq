<?php

namespace Database\Seeders;

use App\Models\ContactPerson;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\EnergyTracker\MeasurementDeviceContractPrice;
use App\Models\Enums\ContactPersonTypeEnum;
use App\Models\Enums\EnergyTrackerContractBasePriceIntervalEnum;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class MeasurementDeviceContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = UserSeeder::getAdminUser();

        if (!$user instanceof User) {
            $this->command?->warn('MeasurementDeviceContractSeeder: Admin-Benutzer fehlt.');

            return;
        }

        $contacts = $this->seedContacts($user);
        $today = CarbonImmutable::today();

        $electricity = MeasurementDevice::query()
            ->where(MeasurementDevice::user_id, $user->getKey())
            ->where(MeasurementDevice::name, 'Electricity')
            ->first();
        $gas = MeasurementDevice::query()
            ->where(MeasurementDevice::user_id, $user->getKey())
            ->where(MeasurementDevice::name, 'Gas')
            ->first();

        if ($electricity instanceof MeasurementDevice) {
            $currentElectricityContract = $this->seedContract(
                $electricity,
                'Stadtwerke Strom Flex',
                'Stadtwerke München',
                'STROM-2026-001',
                $today->subMonths(8)->startOfMonth(),
                null,
                14.90,
                EnergyTrackerContractBasePriceIntervalEnum::MONTHLY->name,
                true,
            );
            $this->seedPrice($currentElectricityContract, $today->subMonths(8)->startOfMonth(), 0.32);
            $this->seedPrice($currentElectricityContract, $today->subMonths(2)->startOfMonth(), 0.35);
            $this->seedPrice($currentElectricityContract, $today->addMonths(3)->startOfMonth(), 0.39);
            $currentElectricityContract->contacts()->sync([
                $contacts['private']->getKey(),
                $contacts['business']->getKey(),
            ]);

            $historicElectricityContract = $this->seedContract(
                $electricity,
                'Stadtwerke Strom Klassik',
                'Stadtwerke München',
                'STROM-2024-017',
                $today->subYears(2)->startOfMonth(),
                $today->subMonths(9)->endOfMonth(),
                12.50,
                EnergyTrackerContractBasePriceIntervalEnum::MONTHLY->name,
                false,
            );
            $this->seedPrice($historicElectricityContract, $today->subYears(2)->startOfMonth(), 0.28);
            $this->seedPrice($historicElectricityContract, $today->subYear()->startOfMonth(), 0.31);
            $historicElectricityContract->contacts()->sync([
                $contacts['business']->getKey(),
            ]);
        }

        if ($gas instanceof MeasurementDevice) {
            $gasContract = $this->seedContract(
                $gas,
                'Stadtwerke Gas Komfort',
                'Stadtwerke München',
                'GAS-2026-004',
                $today->subMonths(6)->startOfMonth(),
                null,
                11.50,
                EnergyTrackerContractBasePriceIntervalEnum::YEARLY->name,
                true,
            );
            $this->seedPrice($gasContract, $today->subMonths(6)->startOfMonth(), 0.11);
            $this->seedPrice($gasContract, $today->addMonths(2)->startOfMonth(), 0.125);
            $gasContract->contacts()->sync([
                $contacts['business']->getKey(),
            ]);
        }

        if (!$electricity instanceof MeasurementDevice && !$gas instanceof MeasurementDevice) {
            $this->command?->warn('MeasurementDeviceContractSeeder: Keine Demo-Messgeräte gefunden.');
        }
    }

    /**
     * @return array{private: ContactPerson, business: ContactPerson}
     */
    private function seedContacts(User $user): array
    {
        $private = ContactPerson::query()->updateOrCreate(
            [
                ContactPerson::user_id => $user->getKey(),
                ContactPerson::email => 'anna.mueller@example.com',
            ],
            [
                ContactPerson::title => 'Frau',
                ContactPerson::firstname => 'Anna',
                ContactPerson::lastname => 'Müller',
                ContactPerson::phone_private => '+49 170 1234567',
                ContactPerson::phone_business => null,
                ContactPerson::role => 'Private Ansprechpartnerin',
                ContactPerson::type => ContactPersonTypeEnum::PRIVATE->name,
            ],
        );

        $business = ContactPerson::query()->updateOrCreate(
            [
                ContactPerson::user_id => $user->getKey(),
                ContactPerson::email => 'markus.schneider@stadtwerke.example',
            ],
            [
                ContactPerson::title => 'Herr',
                ContactPerson::firstname => 'Markus',
                ContactPerson::lastname => 'Schneider',
                ContactPerson::phone_private => null,
                ContactPerson::phone_business => '+49 89 9876543',
                ContactPerson::role => 'Kundenbetreuung',
                ContactPerson::type => ContactPersonTypeEnum::BUSINESS->name,
            ],
        );

        return [
            'private' => $private,
            'business' => $business,
        ];
    }

    private function seedContract(
        MeasurementDevice $device,
        string            $name,
        string            $provider,
        string            $contractNumber,
        CarbonImmutable   $startsOn,
        ?CarbonImmutable  $endsOn,
        float             $basePrice,
        string            $basePriceInterval,
        bool              $isActive,
    ): MeasurementDeviceContract
    {
        return MeasurementDeviceContract::query()->updateOrCreate(
            [
                MeasurementDeviceContract::measurement_device_id => $device->getKey(),
                MeasurementDeviceContract::name => $name,
            ],
            [
                MeasurementDeviceContract::provider => $provider,
                MeasurementDeviceContract::contract_number => $contractNumber,
                MeasurementDeviceContract::starts_on => $startsOn->toDateString(),
                MeasurementDeviceContract::ends_on => $endsOn?->toDateString(),
                MeasurementDeviceContract::base_price => $basePrice,
                MeasurementDeviceContract::base_price_interval => $basePriceInterval,
                MeasurementDeviceContract::currency => 'EUR',
                MeasurementDeviceContract::is_active => $isActive,
                MeasurementDeviceContract::notes => 'Demo-Daten für Vertrags- und Kostenstatistiken.',
            ],
        );
    }

    private function seedPrice(
        MeasurementDeviceContract $contract,
        CarbonImmutable           $validFrom,
        float                     $unitPrice,
    ): void
    {
        $price = $contract->prices()
            ->whereDate(MeasurementDeviceContractPrice::valid_from, $validFrom->toDateString())
            ->first();
        $attributes = [
            MeasurementDeviceContractPrice::unit_price => $unitPrice,
            MeasurementDeviceContractPrice::notes => 'Demo-Preisstand',
        ];

        if ($price instanceof MeasurementDeviceContractPrice) {
            $price->update($attributes);

            return;
        }

        $contract->prices()->create(array_merge($attributes, [
            MeasurementDeviceContractPrice::valid_from => $validFrom->toDateString(),
        ]));
    }
}
