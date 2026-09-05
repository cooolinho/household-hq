<?php

namespace Database\Seeders\Financial;

use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use Illuminate\Database\Seeder;

/**
 * TransactionCategorySeeder
 *
 * Erstellt die Transaktionskategorien mit ihren Unterkategorien.
 *
 * Als Default-Kriterium erhält jede Unterkategorie eine Regel, die prüft, ob der
 * Name der Unterkategorie im Verwendungszweck (purpose) enthalten ist.
 *
 * Struktur (Hauptkategorie > Unterkategorien):
 *  - Finanzen & Versicherungen > Bankgebühren, Beruf & Gewerbe, Dienstleistungen,
 *      Geldautomat, Immobilien, Kindergeld & Unterhalt, Kredite & Finanzierungen,
 *      Lohn & Gehalt, Sparen, Umbuchung, Versicherungen, Zinsen & Investitionen
 *  - Freizeit & Unterhaltung > Ausflüge & Aktivität, Glücksspiel, Hobby,
 *      Kunst & Kultur, Medien, Sport, Streaming, Urlaub, Vereine
 *  - Lebenshaltung > Baumarkt & Gartencenter, Gastronomie, Geschenke, Gesundheit,
 *      Handy & Internet, Haustier, Kinder, Körperpflege & Wellness,
 *      Lebensmittel & Getränke, Möbel & Einrichtung, Shopping, Wohnen & Wohnnebenkosten
 *  - Mobilität > Auto & Motorrad, Fahrrad & Scooter, Tank- & Ladestelle, Verkehrsmittel
 *  - Staat & Behörde > Amts- & Verwaltungsgebühren, Rundfunkbeitrag,
 *      Sozialleistungen, Steuer
 */
class TransactionCategorySeeder extends Seeder
{
    /**
     * Hauptkategorie => Liste der Unterkategorien.
     *
     * @var array<string, list<string>>
     */
    private const array STRUCTURE = [
        'Finanzen & Versicherungen' => [
            'Bankgebühren',
            'Beruf & Gewerbe',
            'Dienstleistungen',
            'Geldautomat',
            'Immobilien',
            'Kindergeld & Unterhalt',
            'Kredite & Finanzierungen',
            'Lohn & Gehalt',
            'Sparen',
            'Umbuchung',
            'Versicherungen',
            'Zinsen & Investitionen',
        ],
        'Freizeit & Unterhaltung' => [
            'Ausflüge & Aktivität',
            'Glücksspiel',
            'Hobby',
            'Kunst & Kultur',
            'Medien',
            'Sport',
            'Streaming',
            'Urlaub',
            'Vereine',
        ],
        'Lebenshaltung' => [
            'Baumarkt & Gartencenter',
            'Gastronomie',
            'Geschenke',
            'Gesundheit',
            'Handy & Internet',
            'Haustier',
            'Kinder',
            'Körperpflege & Wellness',
            'Lebensmittel & Getränke',
            'Möbel & Einrichtung',
            'Shopping',
            'Wohnen & Wohnnebenkosten',
        ],
        'Mobilität' => [
            'Auto & Motorrad',
            'Fahrrad & Scooter',
            'Tank- & Ladestelle',
            'Verkehrsmittel',
        ],
        'Staat & Behörde' => [
            'Amts- & Verwaltungsgebühren',
            'Rundfunkbeitrag',
            'Sozialleistungen',
            'Steuer',
        ],
    ];

    public static function description(): string
    {
        return 'Legt die Transaktionskategorien mit ihren Unterkategorien und Default-Kriterien an';
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
        foreach (self::STRUCTURE as $parentName => $children) {
            $parent = $this->createCategory($parentName, null);

            foreach ($children as $childName) {
                $child = $this->createCategory($childName, $parent->getKey());
                $this->createDefaultRule($child, $childName);
            }
        }
    }

    private function createCategory(string $name, ?int $parentId): TransactionCategory
    {
        return TransactionCategory::query()->firstOrCreate(
            [
                TransactionCategory::user_id => null,
                TransactionCategory::name => $name,
                TransactionCategory::parent_id => $parentId,
            ],
            [
                TransactionCategory::active => true,
            ],
        );
    }

    /**
     * Default-Kriterium: Der Name der Kategorie muss im Verwendungszweck enthalten sein.
     */
    private function createDefaultRule(TransactionCategory $category, string $name): void
    {
        $rule = TransactionCategoryRule::query()->firstOrCreate(
            [
                TransactionCategoryRule::transaction_category_id => $category->getKey(),
                TransactionCategoryRule::user_id => null,
                TransactionCategoryRule::operator => TransactionCategoryRule::OPERATOR_OR,
            ],
            [
                TransactionCategoryRule::active => true,
            ],
        );

        TransactionCategoryCriterion::query()->firstOrCreate(
            [
                TransactionCategoryCriterion::transaction_category_rule_id => $rule->getKey(),
                TransactionCategoryCriterion::field => TransactionCategoryCriterion::FIELD_PURPOSE,
                TransactionCategoryCriterion::operator => TransactionCategoryCriterion::OP_CONTAINS,
                TransactionCategoryCriterion::value => mb_strtolower($name),
            ],
            [
                TransactionCategoryCriterion::case_sensitive => false,
            ],
        );
    }
}
