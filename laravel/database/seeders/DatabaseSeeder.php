<?php

namespace Database\Seeders;

use Database\Seeders\Core\ContactPersonSeeder;
use Database\Seeders\Core\TagSeeder;
use Database\Seeders\Core\UserSeeder;
use Database\Seeders\EnergyTracker\EnergyTrackerSeeder;
use Database\Seeders\EnergyTracker\MeasurementDeviceContractSeeder;
use Database\Seeders\Financial\BankAccountSeeder;
use Database\Seeders\Financial\BudgetSeeder;
use Database\Seeders\Financial\FixedCostCategorySeeder;
use Database\Seeders\Financial\FixedCostSeeder;
use Database\Seeders\Financial\InsuranceCategorySeeder;
use Database\Seeders\Financial\InsuranceSeeder;
use Database\Seeders\Financial\TransactionCategorySeeder;
use Database\Seeders\Financial\TransactionSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public static function description(): string
    {
        return 'Führt alle Demo-Seeder in Abhängigkeitsreihenfolge aus';
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public static function dependencies(): array
    {
        return [];
    }

    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ContactPersonSeeder::class,
            TagSeeder::class,
            EnergyTrackerSeeder::class,
            MeasurementDeviceContractSeeder::class,
            BankAccountSeeder::class,
            InsuranceCategorySeeder::class,
            InsuranceSeeder::class,
            FixedCostCategorySeeder::class,
            FixedCostSeeder::class,
            TransactionCategorySeeder::class,
            TransactionSeeder::class,
            BudgetSeeder::class,
        ]);
    }
}
