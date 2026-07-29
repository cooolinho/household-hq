<?php

namespace Database\Seeders;

use App\Models\Enums\FixedCostCategoryEnum;
use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Insurance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FixedCostSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = UserSeeder::getAdminUser();

        FixedCost::query()->create([
            FixedCost::name => 'Gehalt',
            FixedCost::amount => 2000.00,
            FixedCost::category => FixedCostCategoryEnum::OTHER,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
            FixedCost::user_id => $user->id,
        ]);

        FixedCost::query()->create([
            FixedCost::name => 'Miete',
            FixedCost::amount => -1000.00,
            FixedCost::category => FixedCostCategoryEnum::OTHER,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
            FixedCost::user_id => $user->id,
            FixedCost::next_booking_date => Carbon::now()->addMonth()->startOfMonth(),
        ]);

        FixedCost::query()->create([
            FixedCost::name => 'Hausrat-Versicherung',
            FixedCost::amount => -100.00,
            FixedCost::category => FixedCostCategoryEnum::OTHER,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
            FixedCost::insurance_id => Insurance::query()->where(Insurance::name, 'Hausrat Versicherung')->first()->id,
            FixedCost::user_id => $user->id,
            FixedCost::next_booking_date => Carbon::now()->addMonth()->startOfMonth(),
        ]);
    }
}
