<?php

namespace Database\Seeders\Financial;

use App\Models\Financial\InsuranceCategory;
use Illuminate\Database\Seeder;

class InsuranceCategorySeeder extends Seeder
{
    public static function description(): string
    {
        return 'Legt Demo-Versicherungskategorien inklusive Nicht kategorisiert an';
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
        $categories = [
            'Altersvorsorge' => [
                'Zulagenrente',
                'Betriebliche Altersvorsorge',
                'Persönliche Basisvorsorge',
                'Flexible Privatvorsorge',
            ],
            'Persönliche Absicherung' => [
                'Private KV / Gesetzliche KV',
                'Kranken-Zusatzversicherung',
                'Berufsunfähigkeit',
                'Pflegeversicherung',
                'Unfallversicherung',
                'Risikolebensversicherung',
            ],
            'Vermögensabsicherung' => [
                'Haftpflichtversicherung',
                'Hausratversicherung',
                'Rechtsschutzversicherung',
                'Kfz-Versicherung',
                'Sachbündelversicherung',
            ],
            'Vermögensaufbau' => [
                'Notgroschen / Notfallfonds',
                'Vermögenswirksame Leistungen (VL)',
                'Sparpläne (z.B. ETF, Fonds, Aktien)',
                'Einmalanlagen (z.B. Festgeld, Anleihen)',
                'Sonstige Vermögensaufbau-Produkte',
            ],
            InsuranceCategory::GROUP_NOT_CATEGORIZED => [
                'Sonstige',
            ],
        ];

        foreach ($categories as $group => $names) {
            foreach ($names as $name) {
                InsuranceCategory::query()->firstOrCreate(
                    [
                        InsuranceCategory::group => $group,
                        InsuranceCategory::name => $name,
                    ],
                );
            }
        }
    }
}
