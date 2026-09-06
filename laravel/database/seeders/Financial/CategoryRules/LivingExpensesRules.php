<?php

namespace Database\Seeders\Financial\CategoryRules;

/**
 * Regeln der Hauptkategorie "Lebenshaltung".
 */
class LivingExpensesRules extends CategoryRuleProvider
{
    public static function parentName(): string
    {
        return 'Lebenshaltung';
    }

    /**
     * @return array<string, SubcategoryRules>
     */
    public static function subcategories(): array
    {
        return [
            'Baumarkt & Gartencenter' => new SubcategoryRules(
                merchants: [
                    'obi', 'bauhaus', 'hornbach', 'toom baumarkt', 'hagebaumarkt', 'hagebau',
                    'hellweg', 'globus baumarkt', 'dehner', 'pflanzen kölle', 'gartencenter',
                    'raiffeisen markt', 'sagaflor', 'bauking',
                ],
                keywords: [
                    'baumarkt', 'gartenbedarf', 'pflanzenkauf', 'blumenerde', 'gartenmöbel',
                    'werkzeugkauf', 'baustoffe', 'gartengeräte', 'rasenmäher',
                ],
            ),

            'Gastronomie' => new SubcategoryRules(
                merchants: [
                    'mcdonald', 'burger king', 'kfc', 'subway', 'nordsee', 'vapiano',
                    'RAW:l[\'`´]?\s?osteria', 'starbucks', 'dunkin', 'lieferando', 'uber eats',
                    'wolt', 'RAW:domino[\'`´]?s', 'dean & david', 'hans im glück', 'block house',
                    'backwerk', 'kamps', 'peter pane', 'five guys',
                ],
                keywords: [
                    'restaurant', 'gaststätte', 'gasthaus', 'brauhaus', 'pizzeria', 'trattoria',
                    'bistro', 'imbiss', 'döner', 'RAW:\bcaf[eé]\b', 'bäckerei', 'konditorei',
                    'eisdiele', 'kantine', 'mensa', 'foodcourt', 'lieferdienst', 'trinkgeld',
                    'gastronomie', 'biergarten',
                ],
                amountGuard: SubcategoryRules::GUARD_EXPENSE,
            ),

            'Geschenke' => new SubcategoryRules(
                merchants: [
                    'fleurop', 'blume2000', 'blumen risse', 'valentins', 'mydays', 'jochen schweizer',
                ],
                keywords: [
                    'geschenk', 'geschenkkauf', 'geschenkgutschein', 'geschenkkarte', 'blumenstrauß',
                    'geburtstagsgeschenk', 'hochzeitsgeschenk', 'weihnachtsgeschenk', 'präsent',
                    'geldgeschenk', 'gutschein',
                ],
            ),

            'Gesundheit' => new SubcategoryRules(
                merchants: [
                    'fielmann', 'apollo optik', 'mister spex', 'brillen.de', 'sanitätshaus',
                    'docmorris', 'shop-apotheke', 'aok', 'barmer', 'techniker krankenkasse',
                    'dak-gesundheit', 'ikk classic', 'kkh', 'hkk', 'knappschaft', 'pronova bkk',
                    'mhplus',
                ],
                keywords: [
                    'apotheke', 'arztpraxis', 'praxisgebühr', 'zahnarzt', 'kieferorthopäd',
                    'hausarzt', 'facharzt', 'klinik', 'krankenhaus', 'physiotherapie', 'ergotherapie',
                    'logopädie', 'heilpraktiker', 'laborkosten', 'rezeptgebühr', 'zuzahlung',
                    'hörgerät', 'brillenkauf', 'kontaktlinsen', 'krankenkassenbeitrag', 'medikamente',
                    'impfung', 'vorsorgeuntersuchung',
                ],
            ),

            'Handy & Internet' => new SubcategoryRules(
                merchants: [
                    'telekom', 'deutsche telekom', 'vodafone', 'RAW:\bo2\b', 'telefónica',
                    'RAW:\b1\s?&\s?1\b', 'congstar', 'aldi talk', 'blau.de', 'winsim', 'simyo',
                    'pyur', 'unitymedia', 'm-net', 'netcologne', 'ewe tel', 'htp', 'wilhelm.tel',
                ],
                keywords: [
                    'mobilfunk', 'mobilfunkrechnung', 'handyvertrag', 'handyrechnung', 'dsl',
                    'dsl-anschluss', 'internetanschluss', 'internetrechnung', 'glasfaser',
                    'festnetz', 'telefonrechnung', 'prepaid aufladung', 'datenvolumen',
                    'telefonanschluss', 'kabelanschluss',
                ],
                amountGuard: SubcategoryRules::GUARD_EXPENSE,
            ),

            'Haustier' => new SubcategoryRules(
                merchants: [
                    'fressnapf', 'zooplus', 'das futterhaus', 'zoo & co', 'futterhaus', 'agila',
                    'petplan', 'zoohandlung', 'tierarztpraxis',
                ],
                keywords: [
                    'tierarzt', 'tierklinik', 'tierheim', 'tierpension', 'hundeschule', 'hundesteuer',
                    'tierfutter', 'hundefutter', 'katzenfutter', 'katzenstreu', 'tierbedarf',
                    'tierversicherung', 'hundehaftpflicht', 'hundemarke', 'tierhalterhaftpflicht',
                ],
            ),

            'Kinder' => new SubcategoryRules(
                merchants: [
                    'mytoys', 'babymarkt', 'jako-o', 'tausendkind', 'smyths toys', 'toys r us',
                    'babyone', 'baby walz',
                ],
                keywords: [
                    'kita', 'kitabeitrag', 'kindergarten', 'kindertagesstätte', 'kinderbetreuung',
                    'tagesmutter', 'hortbeitrag', 'schulgeld', 'schulbedarf', 'klassenfahrt',
                    'schulmaterial', 'schulausflug', 'kinderturnen', 'musikschule', 'babyausstattung',
                    'windeln', 'spielzeug', 'kinderkleidung', 'essensgeld',
                ],
            ),

            'Körperpflege & Wellness' => new SubcategoryRules(
                merchants: [
                    'dm-drogerie', 'dm drogeriemarkt', 'rossmann', 'müller drogerie',
                    'müller drogeriemarkt', 'douglas', 'parfümerie', 'flaconi', 'notino',
                    'klier', 'super cut', 'the body shop', 'lush',
                ],
                keywords: [
                    'friseur', 'barbershop', 'barbier', 'kosmetikstudio', 'nagelstudio', 'fußpflege',
                    'maniküre', 'massage', 'wellness', 'spa-behandlung', 'sauna', 'solarium',
                    'drogerie', 'körperpflege', 'kosmetik', 'haarschnitt',
                ],
                amountGuard: SubcategoryRules::GUARD_EXPENSE,
            ),

            'Lebensmittel & Getränke' => new SubcategoryRules(
                merchants: [
                    'rewe', 'edeka', 'aldi süd', 'aldi nord', 'aldi sued', 'lidl', 'kaufland',
                    'penny', 'netto marken-discount', 'RAW:\bnetto\b', 'norma', 'famila', 'tegut',
                    'combi', 'marktkauf', 'nahkauf', 'denns biomarkt', 'alnatura', 'bio company',
                    'RAW:real,-', 'globus sb-warenhaus', 'metro', 'getränkemarkt', 'trinkgut',
                    'fristo', 'hol ab', 'flink', 'gorillas', 'picnic', 'knuspr', 'wasgau', 'v-markt',
                ],
                keywords: [
                    'lebensmittel', 'lebensmitteleinkauf', 'wocheneinkauf', 'supermarkt',
                    'getränkekauf', 'obst und gemüse', 'metzgerei', 'fleischerei', 'hofladen',
                    'wochenmarkt', 'einkauf lebensmittel',
                ],
                amountGuard: SubcategoryRules::GUARD_EXPENSE,
            ),

            'Möbel & Einrichtung' => new SubcategoryRules(
                merchants: [
                    'ikea', 'xxxlutz', 'xxxl', 'höffner', 'möbel höffner', 'poco einrichtungsmarkt',
                    'möbel roller', 'segmüller', 'porta möbel', 'mömax', 'dänisches bettenlager',
                    'jysk', 'home24', 'butlers', 'westwing', 'connox', 'moebel.de', 'sconto',
                ],
                keywords: [
                    'möbelkauf', 'einrichtung', 'wohnaccessoires', 'matratze', 'bettwäsche',
                    'gardinen', 'teppich', 'küchenmöbel', 'wohnzimmermöbel', 'lampenkauf', 'deko',
                    'einbauküche', 'polstermöbel',
                ],
            ),

            'Shopping' => new SubcategoryRules(
                merchants: [
                    'amazon', 'amazon.de', 'otto gmbh', 'otto versand', 'zalando', 'about you',
                    'RAW:\bh\s?&\s?m\b', 'RAW:\bc\s?&\s?a\b', 'zara', 'primark', 'tk maxx',
                    'peek & cloppenburg', 'galeria karstadt kaufhof', 'media markt', 'mediamarkt',
                    'saturn', 'ebay', 'etsy', 'shein', 'temu', 'asos', 'deichmann', 'snipes',
                    'foot locker', 'breuninger', 'tchibo', 'action', 'woolworth', 'kik',
                    'ernstings family', 'aboutyou',
                ],
                keywords: [
                    'onlinebestellung', 'onlinekauf', 'bestellung nr', 'warenkauf', 'bekleidung',
                    'schuhkauf', 'elektronikkauf', 'versandhandel', 'onlineshop',
                ],
            ),

            'Wohnen & Wohnnebenkosten' => new SubcategoryRules(
                merchants: [
                    'stadtwerke', 'RAW:\be\.?\s?on\b', 'vattenfall', 'RAW:\brwe\b', 'enbw',
                    'lichtblick', 'yello strom', 'eprimo', 'naturstrom', 'octopus energy',
                    'vonovia', 'deutsche wohnen', 'leg wohnen', 'wohnungsgenossenschaft',
                    'hausverwaltung', 'abfallwirtschaft', 'entsorgungsbetriebe', 'techem', 'ista',
                ],
                keywords: [
                    'miete', 'kaltmiete', 'warmmiete', 'mietzahlung', 'nebenkosten', 'betriebskosten',
                    'nebenkostenabrechnung', 'hausgeld', 'kaution', 'mietkaution', 'stromabschlag',
                    'gasabschlag', 'RAW:\bstrom\b', 'RAW:\bgas\b', 'wasser', 'abwasser', 'fernwärme',
                    'heizkosten', 'heizöl', 'müllgebühr', 'müllabfuhr', 'straßenreinigung',
                    'schornsteinfeger', 'grundbesitzabgaben', 'wohnnebenkosten',
                ],
            ),
        ];
    }
}
