<?php

namespace Database\Seeders\Financial\CategoryRules;

/**
 * Regeln der Hauptkategorie "Mobilität".
 */
class MobilityRules extends CategoryRuleProvider
{
    public static function parentName(): string
    {
        return 'Mobilität';
    }

    /**
     * @return array<string, SubcategoryRules>
     */
    public static function subcategories(): array
    {
        return [
            'Auto & Motorrad' => new SubcategoryRules(
                merchants: [
                    'RAW:\ba\.?t\.?u\.?\b', 'pitstop', 'vergölst', 'euromaster', 'reifen.com',
                    'reifendirekt', 'carglass', 'autohaus', 'autoteile unger', 'kfzteile24',
                    'louis motorrad', 'detlev louis', 'polo motorrad', 'tüv süd', 'tüv nord',
                    'tüv rheinland', 'tüv hessen', 'dekra', 'gtü', 'apcoa', 'contipark', 'q-park',
                    'easypark', 'parkster', 'sixt', 'europcar', 'mietwagen',
                ],
                keywords: [
                    'RAW:\bkfz\b', 'autowerkstatt', 'autoreparatur', 'inspektion', 'hauptuntersuchung',
                    'abgasuntersuchung', 'ölwechsel', 'reifenwechsel', 'autoteile', 'ersatzteile',
                    'motorrad', 'autowäsche', 'waschanlage', 'parkgebühr', 'parkhaus', 'parkschein',
                    'anhängermiete', 'autokauf', 'werkstattrechnung', 'abschleppdienst',
                ],
            ),

            'Fahrrad & Scooter' => new SubcategoryRules(
                merchants: [
                    'rose bikes', 'RAW:\bbike24\b', 'bike-discount', 'lucky bike', 'fahrrad xxl',
                    'zweirad stadler', 'canyon bicycles', 'swapfiets', 'nextbike', 'call a bike',
                    'tier mobility', 'voi technology', 'RAW:\blime\b', 'bird rides', 'dott',
                ],
                keywords: [
                    'fahrrad', 'fahrradkauf', 'fahrradladen', 'fahrradwerkstatt', 'fahrradzubehör',
                    'RAW:\be-?bike\b', 'pedelec', 'lastenrad', 'zweiradhandel', 'e-scooter',
                    'tretroller', 'radservice', 'fahrradreparatur', 'fahrradmiete',
                ],
            ),

            'Tank- & Ladestelle' => new SubcategoryRules(
                merchants: [
                    'aral', 'shell', 'esso', 'RAW:\bjet\b', 'totalenergies', 'total tankstelle',
                    'agip', 'RAW:\beni\b', 'star tankstelle', 'avia', 'RAW:\bhem\b', 'orlen',
                    'raiffeisen tankstelle', 'westfalen tankstelle', 'ionity', 'ewe go', 'allego',
                    'plugsurfing', 'RAW:\belli\b', 'maingau energie', 'tesla supercharger',
                    'shell recharge',
                ],
                keywords: [
                    'tankstelle', 'tankvorgang', 'kraftstoff', 'benzin', 'diesel', 'super e10',
                    'adblue', 'ladevorgang', 'ladestation', 'ladepunkt', 'ladesäule', 'ladekarte',
                    'stromtanken', 'tankquittung', 'tanken',
                ],
                amountGuard: SubcategoryRules::GUARD_EXPENSE,
            ),

            'Verkehrsmittel' => new SubcategoryRules(
                merchants: [
                    'db vertrieb', 'deutsche bahn', 'db fernverkehr', 'db regio', 'bahn.de',
                    'flixbus', 'flixtrain', 'blablacar', 'RAW:\buber\b', 'free now', 'bolt europe',
                    'bvg', 'hvv', 'RAW:\bmvg\b', 'RAW:\bmvv\b', 'RAW:\brmv\b', 'RAW:\bvrr\b',
                    'RAW:\bvrs\b', 'RAW:\bvvs\b', 'RAW:\bkvb\b', 'üstra', 'RAW:\brnv\b',
                    'verkehrsbetriebe', 'verkehrsverbund', 'rheinbahn', 'moia',
                ],
                keywords: [
                    'deutschlandticket', 'jobticket', 'monatskarte', 'jahreskarte', 'wochenkarte',
                    'fahrkarte', 'zugticket', 'bahnticket', 'bahncard', 'nahverkehr', 'öpnv',
                    'taxifahrt', 'RAW:\btaxi\b', 'fährticket', 'fahrschein', 'semesterticket',
                ],
            ),
        ];
    }
}
