<?php

namespace Database\Seeders\Financial\CategoryRules;

/**
 * Regeln der Hauptkategorie "Staat & Behörde".
 */
class GovernmentRules extends CategoryRuleProvider
{
    public static function parentName(): string
    {
        return 'Staat & Behörde';
    }

    /**
     * @return array<string, SubcategoryRules>
     */
    public static function subcategories(): array
    {
        return [
            'Amts- & Verwaltungsgebühren' => new SubcategoryRules(
                merchants: [
                    'bürgeramt', 'bürgerbüro', 'einwohnermeldeamt', 'standesamt', 'zulassungsstelle',
                    'ordnungsamt', 'amtsgericht', 'gerichtskasse', 'justizkasse', 'landeskasse',
                    'stadtkasse', 'kreiskasse', 'bundeskasse', 'kraftfahrt-bundesamt',
                    'landratsamt', 'ausländerbehörde',
                ],
                keywords: [
                    'verwaltungsgebühr', 'verwaltungsgebühren', 'amtsgebühr', 'gebührenbescheid',
                    'verwarngeld', 'verwarnungsgeld', 'bußgeld', 'bußgeldbescheid',
                    'ordnungswidrigkeit', 'knöllchen', 'kfz-zulassung', 'führerschein',
                    'personalausweis', 'reisepass', 'beglaubigung', 'apostille', 'gerichtskosten',
                    'meldebescheinigung', 'führungszeugnis',
                ],
            ),

            'Rundfunkbeitrag' => new SubcategoryRules(
                merchants: [
                    'ard zdf deutschlandradio', 'RAW:ard\s?\/\s?zdf', 'beitragsservice',
                    'RAW:\bgez\b', 'rundfunk ard',
                ],
                keywords: [
                    'rundfunkbeitrag', 'rundfunkgebühr', 'beitragsnummer', 'rundfunkbeitragsservice',
                ],
                amountGuard: SubcategoryRules::GUARD_EXPENSE,
            ),

            'Sozialleistungen' => new SubcategoryRules(
                merchants: [
                    'jobcenter', 'agentur für arbeit', 'bundesagentur für arbeit', 'sozialamt',
                    'versorgungsamt', 'deutsche rentenversicherung', 'elterngeldstelle',
                    'wohngeldstelle', 'bafög-amt', 'amt für ausbildungsförderung',
                ],
                keywords: [
                    'bürgergeld', 'arbeitslosengeld', 'RAW:\balg\s?(?:i{1,2}|[12])\b',
                    'grundsicherung', 'sozialhilfe', 'wohngeld', 'lastenzuschuss', 'elterngeld',
                    'bafög', 'ausbildungsförderung', 'rentenzahlung', 'altersrente',
                    'erwerbsminderungsrente', 'krankengeld', 'pflegegeld', 'mutterschaftsgeld',
                    'kurzarbeitergeld',
                ],
                amountGuard: SubcategoryRules::GUARD_INCOME,
            ),

            'Steuer' => new SubcategoryRules(
                merchants: [
                    'finanzamt', 'bundeszentralamt für steuern', 'elster', 'steuerkasse',
                ],
                keywords: [
                    'RAW:\bsteuer(?!berat)\w*', 'einkommensteuer', 'lohnsteuer', 'umsatzsteuer',
                    'gewerbesteuer', 'grundsteuer', 'kirchensteuer', 'kapitalertragsteuer',
                    'solidaritätszuschlag', 'RAW:\bkfz-?steuer\w*', 'kraftfahrzeugsteuer',
                    'steuervorauszahlung', 'steuernachzahlung', 'steuererstattung', 'steuerbescheid',
                ],
            ),
        ];
    }
}
