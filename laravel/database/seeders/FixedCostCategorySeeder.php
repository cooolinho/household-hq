<?php

namespace Database\Seeders;

use App\Models\Financial\FixedCostCategory;
use Illuminate\Database\Seeder;

class FixedCostCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Energie' => ['Strom', 'Gas', 'Oel', 'Wasser', 'Sonstige Energie'],
            'Einkommen' => ['Lohn / Gehalt', 'Rente', 'Kindergeld', 'Arbeitslosengeld', 'Sonstige Einnahmen'],
            'Kommunikation' => ['Internet', 'Mobiltelefon', 'Telefon', 'Cloud-Speicher', 'Sonstige Kommunikation'],
            'Versicherungen' => ['Auto', 'Motorrad', 'Gesundheit', 'Unfall', 'Erwerbsunfaehigkeit', 'Hausrat', 'Gebaeude', 'Rechtsschutz', 'Leben', 'Kredite', 'Haftpflicht', 'Rente', 'Sonstige Versicherungen'],
            'Kredite' => ['Autokredit', 'Umschuldung', 'Unterhaltungskredit', 'Moebel / Renovierung', 'Immobilienkredit', 'Studentenkredit', 'Bausparvertrag', 'Freie Verfuegung'],
            'Unterhaltung' => ['Musik-Streaming', 'Rundfunkbeitrag', 'Pay-TV', 'Video-Streaming', 'Sonstige Unterhaltung'],
            'Leasing' => ['Autoleasing', 'Sonstiges Leasing'],
            null => ['Sparen', 'Spenden', 'Hypothek / Miete', 'Kinderbetreuung', 'Gesundheit', 'Bankgebuehren', 'Sonstige'],
        ];

        foreach ($categories as $group => $names) {
            foreach ($names as $name) {
                FixedCostCategory::query()->updateOrCreate([
                    FixedCostCategory::group => $group,
                    FixedCostCategory::name => $name,
                ]);
            }
        }
    }
}

