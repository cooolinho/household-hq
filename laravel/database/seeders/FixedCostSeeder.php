<?php

namespace Database\Seeders;

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

        // --- Bestehende Fixkosten (aktualisiert mit next_booking_date) ----------

        FixedCost::query()->create([
            FixedCost::name => 'Gehalt',
            FixedCost::amount => 2000.00,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
            FixedCost::user_id => $user->id,
            FixedCost::next_booking_date => Carbon::now()->endOfMonth()->startOfDay(), // 2026-07-31
        ]);

        FixedCost::query()->create([
            FixedCost::name => 'Miete',
            FixedCost::amount => -1000.00,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
            FixedCost::user_id => $user->id,
            FixedCost::next_booking_date => Carbon::now()->addMonth()->startOfMonth(), // 2026-08-01
        ]);

        FixedCost::query()->create([
            FixedCost::name => 'Hausrat-Versicherung',
            FixedCost::amount => -100.00,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
            FixedCost::insurance_id => Insurance::query()->where(Insurance::name, 'Hausrat Versicherung')->first()->id,
            FixedCost::user_id => $user->id,
            FixedCost::next_booking_date => Carbon::now()->addMonth()->startOfMonth(),
        ]);

        // --- Test-Fixkosten für Matching-Szenarien ----------------------------

        // Szenario 1: wird eindeutig mit tx_spotify verknüpft (Score 100)
        FixedCost::query()->create([
            FixedCost::name => 'Spotify',
            FixedCost::amount => -9.99,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
            FixedCost::user_id => $user->id,
            FixedCost::next_booking_date => Carbon::now()->startOfDay(), // 2026-07-29
        ]);

        // Szenario 2: wird eindeutig mit tx_netflix verknüpft (Score 100)
        FixedCost::query()->create([
            FixedCost::name => 'Netflix',
            FixedCost::amount => -15.99,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
            FixedCost::user_id => $user->id,
            FixedCost::next_booking_date => Carbon::now()->addMonth()->startOfMonth(), // 2026-08-01
        ]);

        // Szenario 5: wird eindeutig mit tx_strom verknüpft (Score ~85, Betrag -79.50 vs -80)
        FixedCost::query()->create([
            FixedCost::name => 'Strom',
            FixedCost::amount => -80.00,
            FixedCost::interval => FixedCostIntervalEnum::TWO_MONTHS,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
            FixedCost::user_id => $user->id,
            FixedCost::next_booking_date => Carbon::parse('2026-08-15'), // 1 Tag von tx_strom entfernt
        ]);

        // Szenario 6a: Teil des Gleichstands mit tx_amazon → erzeugt Suggestion
        FixedCost::query()->create([
            FixedCost::name => 'Amazon Prime',
            FixedCost::amount => -8.99,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
            FixedCost::user_id => $user->id,
            FixedCost::next_booking_date => Carbon::now()->startOfDay(), // 2026-07-29
        ]);

        // Szenario 6b: zweiter Teil des Gleichstands mit tx_amazon → erzeugt Suggestion
        FixedCost::query()->create([
            FixedCost::name => 'Amazon Prime',      // identischer Name + Betrag → exakt gleicher Score
            FixedCost::amount => -8.99,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE,
            FixedCost::user_id => $user->id,
            FixedCost::next_booking_date => Carbon::now()->startOfDay(), // 2026-07-29
        ]);
    }
}
