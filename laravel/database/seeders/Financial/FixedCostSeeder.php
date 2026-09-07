<?php

namespace Database\Seeders\Financial;

use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostIntervalUnitEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Insurance;
use App\Models\User;
use Database\Seeders\Core\UserSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FixedCostSeeder extends Seeder
{
    public static function description(): string
    {
        return 'Legt Demo-Fixkosten für Matching-Szenarien an';
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public static function dependencies(): array
    {
        return [
            UserSeeder::class,
            InsuranceSeeder::class,
        ];
    }

    public function run(): void
    {
        $user = UserSeeder::getAppUser();

        if (!$user instanceof User) {
            $this->command?->warn('FixedCostSeeder: Admin-Benutzer fehlt.');

            return;
        }

        $insurance = Insurance::query()
            ->where(Insurance::user_id, $user->getKey())
            ->where(Insurance::name, 'Hausrat Versicherung')
            ->first();

        if (!$insurance instanceof Insurance) {
            $this->command?->warn('FixedCostSeeder: Hausrat-Versicherung fehlt.');

            return;
        }

        $startDate = Carbon::now()->startOfDay();
        $fixedCosts = [
            [
                FixedCost::name => 'Gehalt',
                FixedCost::amount => 2000.00,
                FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
                FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
                FixedCost::notes => 'Demo-Fixkosten: Gehalt',
                FixedCost::next_booking_date => $startDate->copy()->endOfMonth()->startOfDay(),
            ],
            [
                FixedCost::name => 'Miete',
                FixedCost::amount => -1000.00,
                FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
                FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
                FixedCost::notes => 'Demo-Fixkosten: Miete',
                FixedCost::next_booking_date => $startDate->copy()->addMonth()->startOfMonth(),
            ],
            [
                FixedCost::name => 'Hausrat-Versicherung',
                FixedCost::amount => -100.00,
                FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
                FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
                FixedCost::insurance_id => $insurance->getKey(),
                FixedCost::notes => 'Demo-Fixkosten: Hausrat',
                FixedCost::next_booking_date => $startDate->copy()->addMonth()->startOfMonth(),
            ],
            [
                FixedCost::name => 'Spotify',
                FixedCost::amount => -9.99,
                FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
                FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
                FixedCost::notes => 'Demo-Matching: Spotify',
                FixedCost::next_booking_date => $startDate->copy(),
            ],
            [
                FixedCost::name => 'Netflix',
                FixedCost::amount => -15.99,
                FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
                FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
                FixedCost::notes => 'Demo-Matching: Netflix',
                FixedCost::next_booking_date => $startDate->copy()->addMonth()->startOfMonth(),
            ],
            [
                FixedCost::name => 'Strom',
                FixedCost::amount => -80.00,
                FixedCost::interval => FixedCostIntervalEnum::TWO_MONTHS,
                FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
                FixedCost::notes => 'Demo-Matching: Strom',
                FixedCost::next_booking_date => $startDate->copy(),
            ],
            [
                FixedCost::name => 'Wartungsvertrag',
                FixedCost::amount => -120.00,
                FixedCost::interval => FixedCostIntervalEnum::CUSTOM,
                FixedCost::custom_interval_value => 4,
                FixedCost::custom_interval_unit => FixedCostIntervalUnitEnum::MONTH->name,
                FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
                FixedCost::notes => 'Demo-Fixkosten: Custom alle 4 Monate',
                FixedCost::next_booking_date => $startDate->copy()->addMonths(2)->startOfMonth(),
            ],
            [
                FixedCost::name => 'Amazon Prime',
                FixedCost::amount => -8.99,
                FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
                FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
                FixedCost::notes => 'Demo-Matching: Amazon Prime A',
                FixedCost::next_booking_date => $startDate->copy(),
            ],
            [
                FixedCost::name => 'Amazon Prime',
                FixedCost::amount => -8.99,
                FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
                FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
                FixedCost::notes => 'Demo-Matching: Amazon Prime B',
                FixedCost::next_booking_date => $startDate->copy(),
            ],
        ];

        foreach ($fixedCosts as $fixedCost) {
            FixedCost::query()->firstOrCreate(
                [
                    FixedCost::user_id => $user->getKey(),
                    FixedCost::name => $fixedCost[FixedCost::name],
                    FixedCost::notes => $fixedCost[FixedCost::notes],
                ],
                $fixedCost,
            );
        }
    }
}
