<?php

namespace Tests\Unit;

use App\Models\Financial\InsuranceCategory;
use Database\Seeders\Financial\InsuranceCategorySeeder;
use PHPUnit\Framework\TestCase;

class InsuranceCategorySeederTest extends TestCase
{
    public function test_it_contains_all_expected_insurance_categories(): void
    {
        $path = dirname(__DIR__, 2) . '/database/seeders/Financial/InsuranceCategorySeeder.php';

        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $expectedGroups = [
            'Altersvorsorge',
            'Persönliche Absicherung',
            'Vermögensabsicherung',
            'Vermögensaufbau',
            InsuranceCategory::GROUP_NOT_CATEGORIZED,
        ];

        foreach ($expectedGroups as $group) {
            $this->assertStringContainsString($group, $contents);
        }

        $expectedNames = [
            'Zulagenrente',
            'Betriebliche Altersvorsorge',
            'Persönliche Basisvorsorge',
            'Flexible Privatvorsorge',
            'Private KV / Gesetzliche KV',
            'Kranken-Zusatzversicherung',
            'Berufsunfähigkeit',
            'Pflegeversicherung',
            'Unfallversicherung',
            'Risikolebensversicherung',
            'Haftpflichtversicherung',
            'Hausratversicherung',
            'Rechtsschutzversicherung',
            'Kfz-Versicherung',
            'Sachbündelversicherung',
            'Notgroschen / Notfallfonds',
            'Vermögenswirksame Leistungen (VL)',
            'Sparpläne (z.B. ETF, Fonds, Aktien)',
            'Einmalanlagen (z.B. Festgeld, Anleihen)',
            'Sonstige Vermögensaufbau-Produkte',
            'Sonstige',
        ];

        foreach ($expectedNames as $name) {
            $this->assertStringContainsString($name, $contents);
        }

        $this->assertCount(21, $expectedNames);
        $this->assertTrue(class_exists(InsuranceCategorySeeder::class));
    }
}
