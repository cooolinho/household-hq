<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(EnergyTrackerSeeder::class);
        $this->call(TagSeeder::class);
        $this->call(ContactPersonSeeder::class);
        $this->call(BankAccountSeeder::class);
        $this->call(InsuranceSeeder::class);
        $this->call(FixedCostSeeder::class);
    }
}
