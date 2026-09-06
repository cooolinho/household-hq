<?php

namespace Database\Seeders\Financial\CategoryRules;

/**
 * Regeln der Hauptkategorie "Finanzen & Versicherungen".
 */
class FinanceInsuranceRules extends CategoryRuleProvider
{
    public static function parentName(): string
    {
        return 'Finanzen & Versicherungen';
    }

    /**
     * @return array<string, SubcategoryRules>
     */
    public static function subcategories(): array
    {
        return [
            'Bankgebühren' => new SubcategoryRules(
                keywords: [
                    'kontoführungsentgelt', 'kontoführungsgebühr', 'kontogebühr', 'kontoführung',
                    'entgeltabrechnung', 'buchungsposten', 'buchungspostenentgelt', 'postengebühr',
                    'kartenpreis', 'jahrespreis karte', 'kartenentgelt', 'kontoabschluss',
                    'abschluss laut anlage', 'rechnungsabschluss', 'dispozinsen', 'sollzinsen',
                    'überziehungszinsen', 'auslandseinsatzentgelt', 'fremdwährungsentgelt',
                    'bankgebühr', 'bankentgelt', 'depotentgelt',
                ],
                amountGuard: SubcategoryRules::GUARD_EXPENSE,
            ),

            'Beruf & Gewerbe' => new SubcategoryRules(
                merchants: [
                    'ihk', 'industrie- und handelskammer', 'handwerkskammer', 'berufsgenossenschaft',
                    'dekra akademie', 'tüv akademie', 'udemy', 'coursera', 'linkedin learning',
                    'wework', 'regus',
                ],
                keywords: [
                    'gewerbeanmeldung', 'gewerbeummeldung', 'kammerbeitrag', 'kammerumlage',
                    'berufshaftpflicht', 'arbeitsmittel', 'fortbildung', 'weiterbildung', 'seminar',
                    'schulungskosten', 'zertifizierung', 'coworking', 'büromaterial', 'bürobedarf',
                    'dienstreise', 'spesenabrechnung', 'geschäftsausstattung',
                ],
            ),

            'Dienstleistungen' => new SubcategoryRules(
                keywords: [
                    'handwerkerrechnung', 'handwerkerleistung', 'gebäudereinigung', 'hausmeisterservice',
                    'schlüsseldienst', 'umzugsservice', 'umzugsunternehmen', 'notarkosten', 'notargebühr',
                    'rechtsanwalt', 'anwaltskanzlei', 'steuerberater', 'wirtschaftsprüfer', 'gutachterkosten',
                    'sachverständiger', 'schneiderei', 'änderungsschneiderei', 'schuhmacher', 'wäscherei',
                    'textilreinigung', 'dienstleistung', 'servicepauschale', 'servicegebühr',
                ],
            ),

            'Geldautomat' => new SubcategoryRules(
                additionalRules: self::cashMachineRules(),
            ),

            'Immobilien' => new SubcategoryRules(
                merchants: [
                    'immobilienscout24', 'immowelt', 'immonet', 'engel & völkers', 'von poll immobilien',
                    'remax', 'grundbuchamt',
                ],
                keywords: [
                    'grundbucheintrag', 'grundbuchauszug', 'notaranderkonto', 'maklerprovision',
                    'maklercourtage', 'grunderwerbsteuer', 'kaufpreisrate', 'eigentümergemeinschaft',
                    'weg-verwaltung', 'wohngeldabrechnung', 'erbbauzins', 'immobilienkauf',
                    'immobilienverkauf', 'baufinanzierung',
                ],
            ),

            'Kindergeld & Unterhalt' => new SubcategoryRules(
                merchants: [
                    'familienkasse', 'jugendamt', 'unterhaltsvorschusskasse',
                ],
                keywords: [
                    'kindergeld', 'kinderzuschlag', 'kindesunterhalt', 'ehegattenunterhalt',
                    'trennungsunterhalt', 'unterhaltsvorschuss', 'unterhaltszahlung',
                    'RAW:\bunterhalt(?!ung)\w*',
                ],
            ),

            'Kredite & Finanzierungen' => new SubcategoryRules(
                merchants: [
                    'santander', 'targobank', 'creditplus', 'easycredit', 'auxmoney', 'smava',
                    'younited credit', 'klarna', 'consorsfinanz', 'bnp paribas', 'bank11', 'cofidis',
                ],
                keywords: [
                    'RAW:\bkredit(?!karte)\w*', 'darlehen', 'darlehensrate', 'ratenzahlung', 'ratenkredit',
                    'finanzierungsrate', 'tilgung', 'tilgungsrate', 'annuität', 'annuitätendarlehen',
                    'restschuld', 'umschuldung', 'zwischenfinanzierung', 'leasingrate', 'schlussrate',
                ],
            ),

            'Lohn & Gehalt' => new SubcategoryRules(
                keywords: [
                    'lohn', 'gehalt', 'lohn/gehalt', 'gehaltszahlung', 'lohnzahlung', 'gehaltsabrechnung',
                    'verdienstabrechnung', 'bezüge', 'besoldung', 'honorar', 'honorarzahlung',
                    'urlaubsgeld', 'weihnachtsgeld', 'bonuszahlung', 'prämienzahlung', 'tantieme',
                    'abschlagszahlung lohn', 'entgeltzahlung',
                ],
                amountGuard: SubcategoryRules::GUARD_INCOME,
            ),

            'Sparen' => new SubcategoryRules(
                merchants: [
                    'lbs', 'wüstenrot', 'schwäbisch hall', 'bausparkasse', 'debeka bausparkasse',
                    'bhw bausparkasse',
                ],
                keywords: [
                    'sparplan', 'sparbuch', 'sparrate', 'sparbetrag', 'tagesgeld', 'tagesgeldkonto',
                    'festgeld', 'festgeldanlage', 'rücklage', 'bausparen', 'bausparvertrag',
                    'bausparbeitrag', 'vermögenswirksame leistungen', 'vwl', 'notgroschen',
                    'sparübertrag',
                ],
            ),

            'Umbuchung' => new SubcategoryRules(
                keywords: [
                    'umbuchung', 'übertrag', 'kontoübertrag', 'eigenübertrag', 'eigene überweisung',
                    'eigenüberweisung', 'interne buchung', 'saldenausgleich', 'ausgleich girokonto',
                    'übertrag auf eigenes konto', 'kontoumbuchung',
                ],
            ),

            'Versicherungen' => new SubcategoryRules(
                merchants: [
                    'allianz', 'axa versicherung', 'huk-coburg', 'huk24', 'debeka', 'ergo versicherung',
                    'generali', 'signal iduna', 'RAW:\br\+v\s*versicherung', 'devk', 'württembergische',
                    'barmenia', 'gothaer', 'hansemerkur', 'cosmosdirekt', 'zurich versicherung',
                    'nürnberger versicherung', 'provinzial', 'lvm versicherung', 'vhv versicherung',
                    'alte leipziger', 'continentale', 'arag', 'hdi versicherung', 'basler versicherung',
                ],
                keywords: [
                    'versicherung', 'versicherungsbeitrag', 'versicherungsprämie', 'versicherungsschein',
                    'police', 'policennummer', 'haftpflicht', 'hausratversicherung', 'gebäudeversicherung',
                    'rechtsschutz', 'risikoleben', 'lebensversicherung', 'berufsunfähigkeit',
                    'unfallversicherung', 'sterbegeldversicherung', 'selbstbeteiligung', 'beitragsrechnung',
                ],
            ),

            'Zinsen & Investitionen' => new SubcategoryRules(
                merchants: [
                    'trade republic', 'scalable capital', 'comdirect', 'flatex', 'smartbroker',
                    'justtrade', 'degiro', 'interactive brokers', 'coinbase', 'bitpanda', 'kraken.com',
                    'binance', 'etoro', 'weltsparen', 'raisin', 'onvista',
                ],
                keywords: [
                    'zinsen', 'zinsgutschrift', 'zinsabschluss', 'kapitalertrag', 'dividende',
                    'dividendenzahlung', 'ausschüttung', 'wertpapierkauf', 'wertpapierverkauf',
                    'wertpapierabrechnung', 'depotgebühr', 'depotübertrag', 'sparplanausführung',
                    'etf', 'fondsanteile', 'aktienkauf', 'aktienverkauf', 'kryptokauf',
                ],
            ),
        ];
    }

    /**
     * Geldautomat unterscheidet Aus- und Einzahlung, daher zwei getrennte Guard-Gruppen.
     *
     * @return list<RuleDefinition>
     */
    private static function cashMachineRules(): array
    {
        $prefix = self::keyPrefix('Geldautomat');

        $withdrawal = new SubcategoryRules(
            keywords: [
                'bargeldauszahlung', 'geldautomat', 'gaa-auszahlung', 'auszahlung gaa',
                'kartenauszahlung', 'bargeldabhebung', 'barabhebung', 'cash withdrawal',
                'atm withdrawal', 'geldausgabeautomat',
            ],
            amountGuard: SubcategoryRules::GUARD_EXPENSE,
        );

        $deposit = new SubcategoryRules(
            keywords: [
                'bargeldeinzahlung', 'geldeinzahlung', 'bareinzahlung', 'einzahlung sb-terminal',
                'einzahlung geldautomat', 'cash deposit',
            ],
            amountGuard: SubcategoryRules::GUARD_INCOME,
        );

        return [
            ...$withdrawal->toRules($prefix . '.auszahlung'),
            ...$deposit->toRules($prefix . '.einzahlung'),
        ];
    }
}
