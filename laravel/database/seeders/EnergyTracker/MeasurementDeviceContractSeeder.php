<?php

namespace Database\Seeders\EnergyTracker;

use App\Models\ContactPerson;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\EnergyTracker\MeasurementDeviceContractPrice;
use App\Models\Enums\ContactPersonTypeEnum;
use App\Models\Enums\EnergyTrackerContractBasePriceIntervalEnum;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Core\ContactPersonSeeder;
use Database\Seeders\Core\UserSeeder;
use Illuminate\Database\Seeder;

class MeasurementDeviceContractSeeder extends Seeder
{
    public static function description(): string
    {
        return 'Legt Demo-Energieverträge, Preisstände und Ansprechpartner an';
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public static function dependencies(): array
    {
        return [
            UserSeeder::class,
            ContactPersonSeeder::class,
            EnergyTrackerSeeder::class,
        ];
    }

    public function run(): void
    {
        $user = UserSeeder::getAdminUser();

        if (!$user instanceof User) {
            $this->command?->warn('MeasurementDeviceContractSeeder: Admin-Benutzer fehlt.');

            return;
        }

        $contacts = $this->seedContacts($user);
        $startDate = CarbonImmutable::now()->startOfDay();

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
                $startDate->subMonths(8)->startOfMonth(),
                null,
                14.90,
                EnergyTrackerContractBasePriceIntervalEnum::MONTHLY->name,
                true,
            );
            $this->seedPrice($currentElectricityContract, $startDate->subMonths(8)->startOfMonth(), 0.32);
            $this->seedPrice($currentElectricityContract, $startDate->subMonths(2)->startOfMonth(), 0.35);
            $this->seedPrice($currentElectricityContract, $startDate->addMonths(3)->startOfMonth(), 0.39);
            $currentElectricityContract->contacts()->syncWithoutDetaching([
                $contacts['private']->getKey(),
                $contacts['business']->getKey(),
            ]);

            $historicElectricityContract = $this->seedContract(
                $electricity,
                'Stadtwerke Strom Klassik',
                'Stadtwerke München',
                'STROM-2024-017',
                $startDate->subYears(2)->startOfMonth(),
                $startDate->subMonths(9)->endOfMonth(),
                12.50,
                EnergyTrackerContractBasePriceIntervalEnum::MONTHLY->name,
                false,
            );
            $this->seedPrice($historicElectricityContract, $startDate->subYears(2)->startOfMonth(), 0.28);
            $this->seedPrice($historicElectricityContract, $startDate->subYear()->startOfMonth(), 0.31);
            $historicElectricityContract->contacts()->syncWithoutDetaching([
                $contacts['business']->getKey(),
            ]);
        }

        if ($gas instanceof MeasurementDevice) {
            $gasContract = $this->seedContract(
                $gas,
                'Stadtwerke Gas Komfort',
                'Stadtwerke München',
                'GAS-2026-004',
                $startDate->subMonths(6)->startOfMonth(),
                null,
                11.50,
                EnergyTrackerContractBasePriceIntervalEnum::YEARLY->name,
                true,
            );
            $this->seedPrice($gasContract, $startDate->subMonths(6)->startOfMonth(), 0.11);
            $this->seedPrice($gasContract, $startDate->addMonths(2)->startOfMonth(), 0.125);
            $gasContract->contacts()->syncWithoutDetaching([
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
        $private = ContactPerson::query()->firstOrCreate(
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

        $business = ContactPerson::query()->firstOrCreate(
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
        return MeasurementDeviceContract::query()->firstOrCreate(
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
        $contract->prices()->firstOrCreate(
            [
                MeasurementDeviceContractPrice::valid_from => $validFrom->toDateString(),
            ],
            [
                MeasurementDeviceContractPrice::unit_price => $unitPrice,
                MeasurementDeviceContractPrice::notes => 'Demo-Preisstand',
            ],
        );
    }
}
