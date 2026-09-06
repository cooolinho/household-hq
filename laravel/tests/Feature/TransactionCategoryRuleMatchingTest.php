<?php

namespace Tests\Feature;

use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryRule;
use App\Services\TransactionCategorizationService;
use Database\Seeders\Financial\TransactionCategorySeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionCategoryRuleMatchingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: array{0: string, 1: string, 2: string, 3: float}, 1: string}>
     */
    public static function positiveCases(): array
    {
        return [
            // Finanzen & Versicherungen
            'Bankgebühren' => [['Sparkasse Muenchen', 'Entgeltabrechnung', 'Kontofuehrungsentgelt Q3', -12.90], 'Bankgebühren'],
            'Beruf & Gewerbe' => [['IHK Muenchen', 'Lastschrift', 'Kammerbeitrag 2026', -180.00], 'Beruf & Gewerbe'],
            'Dienstleistungen' => [['Kanzlei Schmidt', 'Ueberweisung', 'Rechtsanwalt Beratung', -450.00], 'Dienstleistungen'],
            'Geldautomat' => [['Sparkasse', 'Bargeldauszahlung', 'GAA-Auszahlung Filiale', -200.00], 'Geldautomat'],
            'Immobilien' => [['Notar Dr. Klein', 'Ueberweisung', 'Maklerprovision Kaufvertrag', -8000.00], 'Immobilien'],
            'Kindergeld & Unterhalt' => [['Familienkasse NRW', 'Gutschrift', 'Kindergeld August', 250.00], 'Kindergeld & Unterhalt'],
            'Kredite & Finanzierungen' => [['Santander Bank', 'Lastschrift', 'Darlehensrate Nr 4711', -320.00], 'Kredite & Finanzierungen'],
            'Lohn & Gehalt' => [['Musterfirma GmbH', 'Lohn/Gehalt', 'Gehalt August 2026', 2400.00], 'Lohn & Gehalt'],
            'Sparen' => [['Wuestenrot Bausparkasse', 'Lastschrift', 'Bausparvertrag Rate', -75.00], 'Sparen'],
            'Umbuchung' => [['Eigenes Konto', 'Ueberweisung', 'Uebertrag eigenes Konto', -500.00], 'Umbuchung'],
            'Versicherungen' => [['HUK-COBURG', 'Lastschrift', 'Versicherungsbeitrag Hausrat', -45.00], 'Versicherungen'],
            'Zinsen & Investitionen' => [['Trade Republic', 'Lastschrift', 'Wertpapierabrechnung ETF', -500.00], 'Zinsen & Investitionen'],

            // Freizeit & Unterhaltung
            'Ausflüge & Aktivität' => [['Zoo Leipzig', 'Kartenzahlung', 'Eintrittskarte Familie', -48.00], 'Ausflüge & Aktivität'],
            'Glücksspiel' => [['Tipico Wetten', 'Lastschrift', 'Sportwetten Einsatz', -20.00], 'Glücksspiel'],
            'Hobby' => [['Thomann GmbH', 'Kartenzahlung', 'Musikinstrument Kauf', -350.00], 'Hobby'],
            'Kunst & Kultur' => [['Staatstheater Stuttgart', 'Kartenzahlung', 'Theaterkasse Abo', -78.00], 'Kunst & Kultur'],
            'Medien' => [['Thalia Buchhandlung', 'Kartenzahlung', 'Buchkauf', -24.00], 'Medien'],
            'Sport' => [['FitX GmbH', 'Lastschrift', 'Fitnessstudio Monat', -25.00], 'Sport'],
            'Streaming' => [['Netflix International BV', 'Lastschrift', 'Monatsabo', -15.99], 'Streaming'],
            'Urlaub' => [['TUI Deutschland', 'Lastschrift', 'Pauschalreise Mallorca', -1800.00], 'Urlaub'],
            'Vereine' => [['TSV Musterstadt e.V.', 'Lastschrift', 'Mitgliedsbeitrag 2026', -120.00], 'Vereine'],

            // Lebenshaltung
            'Baumarkt & Gartencenter' => [['OBI Markt', 'Kartenzahlung', 'Baustoffe', -230.00], 'Baumarkt & Gartencenter'],
            'Gastronomie' => [['Restaurant Adler', 'Kartenzahlung', 'Abendessen', -64.00], 'Gastronomie'],
            'Geschenke' => [['Fleurop AG', 'Kartenzahlung', 'Blumenstrauss Geburtstag', -39.00], 'Geschenke'],
            'Gesundheit' => [['Apotheke am Markt', 'Kartenzahlung', 'Medikamente Rezept', -32.50], 'Gesundheit'],
            'Handy & Internet' => [['Telekom Deutschland', 'Lastschrift', 'Mobilfunkrechnung', -39.95], 'Handy & Internet'],
            'Haustier' => [['Fressnapf Tiernahrung', 'Kartenzahlung', 'Hundefutter', -55.00], 'Haustier'],
            'Kinder' => [['Stadt Musterstadt', 'Lastschrift', 'Kitabeitrag August', -180.00], 'Kinder'],
            'Körperpflege & Wellness' => [['Rossmann Drogerie', 'Kartenzahlung', 'Drogerieartikel', -22.00], 'Körperpflege & Wellness'],
            'Lebensmittel & Getränke' => [['REWE Markt GmbH', 'Kartenzahlung', 'Wocheneinkauf', -87.30], 'Lebensmittel & Getränke'],
            'Möbel & Einrichtung' => [['IKEA Deutschland', 'Kartenzahlung', 'Moebelkauf Wohnzimmer', -450.00], 'Möbel & Einrichtung'],
            'Shopping' => [['Amazon EU Sarl', 'Lastschrift', 'Onlinebestellung 302-1', -120.00], 'Shopping'],
            'Wohnen & Wohnnebenkosten' => [['Hausverwaltung Muster GmbH', 'Dauerauftrag', 'Miete August 2026', -1000.00], 'Wohnen & Wohnnebenkosten'],

            // Mobilität
            'Auto & Motorrad' => [['Autohaus Berger', 'Ueberweisung', 'Inspektion und Oelwechsel', -540.00], 'Auto & Motorrad'],
            'Fahrrad & Scooter' => [['Zweirad Stadler', 'Kartenzahlung', 'Fahrradkauf', -899.00], 'Fahrrad & Scooter'],
            'Tank- & Ladestelle' => [['ARAL Tankstelle', 'Kartenzahlung', 'Tanken Super E10', -78.30], 'Tank- & Ladestelle'],
            'Verkehrsmittel' => [['DB Vertrieb GmbH', 'Kartenzahlung', 'Bahnticket ICE', -89.00], 'Verkehrsmittel'],

            // Staat & Behörde
            'Amts- & Verwaltungsgebühren' => [['Buergeramt Musterstadt', 'Kartenzahlung', 'Personalausweis Gebuehr', -37.00], 'Amts- & Verwaltungsgebühren'],
            'Rundfunkbeitrag' => [['ARD ZDF Deutschlandradio', 'Lastschrift', 'Rundfunkbeitrag Q3', -55.08], 'Rundfunkbeitrag'],
            'Sozialleistungen' => [['Agentur fuer Arbeit', 'Gutschrift', 'Arbeitslosengeld August', 1100.00], 'Sozialleistungen'],
            'Steuer' => [['Finanzamt Muenchen', 'Ueberweisung', 'Einkommensteuer Vorauszahlung', -1200.00], 'Steuer'],
        ];
    }

    /**
     * @return array<string, array{0: array{0: string, 1: string, 2: string, 3: float}, 1: string}>
     */
    public static function negativeCases(): array
    {
        return [
            'Transportkosten sind kein Sport' => [
                ['Spedition Meier', 'Ueberweisung', 'Transportkosten Umzug', -350.00],
                'Sport',
            ],
            'Steuerberater ist keine Steuer' => [
                ['Kanzlei Schmidt', 'Ueberweisung', 'Steuerberater Jahresabschluss', -890.00],
                'Steuer',
            ],
            'Kreditkartenabrechnung ist kein Kredit' => [
                ['Sparkasse', 'Abrechnung', 'Kreditkartenabrechnung August', -230.00],
                'Kredite & Finanzierungen',
            ],
            'Unterhaltungselektronik ist kein Unterhalt' => [
                ['MediaMarkt', 'Kartenzahlung', 'Unterhaltungselektronik TV', -799.00],
                'Kindergeld & Unterhalt',
            ],
            'Urlaubsgeld ist kein Urlaub' => [
                ['Arbeitgeber AG', 'Gutschrift', 'Urlaubsgeld 2026', 900.00],
                'Urlaub',
            ],
            'Operation ist keine Oper' => [
                ['Klinikum Nord', 'Ueberweisung', 'Operation Zuzahlung', -250.00],
                'Kunst & Kultur',
            ],
            'Zooplus ist kein Ausflug' => [
                ['Zooplus SE', 'Lastschrift', 'Hundefutter Bestellung', -64.00],
                'Ausflüge & Aktivität',
            ],
            'Sportwetten sind kein Sport' => [
                ['Tipico Wetten', 'Lastschrift', 'Sportwetten Einsatz', -20.00],
                'Sport',
            ],
            'Gaststaette ist kein Gas' => [
                ['Gaststaette Zur Post', 'Kartenzahlung', 'Mittagessen', -24.50],
                'Wohnen & Wohnnebenkosten',
            ],
            'Mietwagen ist keine Miete' => [
                ['Sixt GmbH', 'Kartenzahlung', 'Mietwagen Buchung', -180.00],
                'Wohnen & Wohnnebenkosten',
            ],
            'Guard: negatives Gehalt ist kein Lohneingang' => [
                ['Musterfirma GmbH', 'Lohn/Gehalt', 'Gehalt August 2026', -2400.00],
                'Lohn & Gehalt',
            ],
            'Guard: Rundfunk-Erstattung ist kein Beitrag' => [
                ['ARD ZDF Deutschlandradio', 'Gutschrift', 'Rundfunkbeitrag Erstattung', 55.08],
                'Rundfunkbeitrag',
            ],
            'Guard: Tank-Erstattung ist keine Tankstelle' => [
                ['Arbeitgeber AG', 'Gutschrift', 'Erstattung Tankstelle Dienstfahrt', 78.30],
                'Tank- & Ladestelle',
            ],
        ];
    }

    /**
     * @param array{0: string, 1: string, 2: string, 3: float} $transaction
     */
    #[DataProvider('positiveCases')]
    public function test_transactions_are_matched_to_the_expected_subcategory(
        array  $transaction,
        string $expected,
    ): void
    {
        $matches = $this->match($transaction);

        self::assertContains(
            $expected,
            $matches,
            sprintf(
                'Erwartet "%s", gefunden: %s',
                $expected,
                $matches === [] ? '(keine)' : implode(', ', $matches),
            ),
        );
    }

    /**
     * @param array{0: string, 1: string, 2: string, 3: float} $transaction
     * @return list<string>
     */
    private function match(array $transaction): array
    {
        [$payer, $description, $purpose, $amount] = $transaction;

        $model = new Transaction([
            Transaction::payer => $payer,
            Transaction::description => $description,
            Transaction::purpose => $purpose,
            Transaction::amount => $amount,
        ]);
        $model->user_id = 1;

        return app(TransactionCategorizationService::class)
            ->matchTransaction($model, $this->subcategories())
            ->pluck(TransactionCategory::name)
            ->all();
    }

    /**
     * @return Collection<int, TransactionCategory>
     */
    private function subcategories(): Collection
    {
        $this->seed(TransactionCategorySeeder::class);

        return TransactionCategory::query()
            ->whereNull(TransactionCategory::user_id)
            ->whereNotNull(TransactionCategory::parent_id)
            ->with([
                TransactionCategory::has_many_rules . '.' . TransactionCategoryRule::has_many_criteria,
                TransactionCategory::has_many_rules . '.' . TransactionCategoryRule::has_many_user_settings,
            ])
            ->get();
    }

    /**
     * @param array{0: string, 1: string, 2: string, 3: float} $transaction
     */
    #[DataProvider('negativeCases')]
    public function test_transactions_are_not_matched_to_conflicting_subcategories(
        array  $transaction,
        string $forbidden,
    ): void
    {
        $matches = $this->match($transaction);

        self::assertNotContains(
            $forbidden,
            $matches,
            sprintf('Kategorie "%s" wurde fälschlich zugeordnet.', $forbidden),
        );
    }
}
