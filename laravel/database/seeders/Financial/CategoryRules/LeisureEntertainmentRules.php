<?php

namespace Database\Seeders\Financial\CategoryRules;

use App\Models\Financial\TransactionCategoryCriterion;

/**
 * Regeln der Hauptkategorie "Freizeit & Unterhaltung".
 */
class LeisureEntertainmentRules extends CategoryRuleProvider
{
    public static function parentName(): string
    {
        return 'Freizeit & Unterhaltung';
    }

    /**
     * @return array<string, SubcategoryRules>
     */
    public static function subcategories(): array
    {
        return [
            'Ausflüge & Aktivität' => new SubcategoryRules(
                merchants: [
                    'europa-park', 'phantasialand', 'heide park', 'legoland', 'movie park',
                    'tropical islands', 'hansa-park', 'serengeti park', 'jump house', 'superfly',
                    'boulderwelt', 'therme erding', 'aquadom',
                ],
                keywords: [
                    'zoo', 'tierpark', 'wildpark', 'freizeitpark', 'erlebnisbad', 'aquapark',
                    'kletterhalle', 'boulderhalle', 'hochseilgarten', 'escape room', 'minigolf',
                    'bowling', 'lasertag', 'kartbahn', 'tagesausflug', 'eintrittskarte', 'eintritt',
                    'freizeitbad', 'planetarium',
                ],
            ),

            'Glücksspiel' => new SubcategoryRules(
                merchants: [
                    'westlotto', 'lotto24', 'lotto bayern', 'lotto hessen', 'eurojackpot', 'tipico',
                    'bwin', 'bet365', 'betano', 'interwetten', 'merkur spielothek', 'spielbank',
                    'admiral spielhalle',
                ],
                keywords: [
                    'lotto', 'toto', 'oddset', 'rubbellos', 'sportwette', 'sportwetten', 'wettschein',
                    'casino', 'spielothek', 'poker', 'glücksspiel', 'spieleinsatz', 'lottoschein',
                ],
            ),

            'Hobby' => new SubcategoryRules(
                merchants: [
                    'thomann', 'musikhaus', 'session music', 'conrad electronic', 'reichelt elektronik',
                    'modulor', 'boesner', 'idee creativmarkt', 'gerstaecker', 'askari', 'angelsport',
                    'faller modellbau', 'märklin', 'lego store', 'games workshop',
                ],
                keywords: [
                    'bastelbedarf', 'modellbau', 'nähmaschine', 'stoffe', 'wolle', 'malbedarf',
                    'künstlerbedarf', 'musikinstrument', 'notenkauf', 'angelkarte', 'angelschein',
                    'fotoausrüstung', 'hobbybedarf', 'werkstattbedarf', 'brettspiel',
                ],
            ),

            'Kunst & Kultur' => new SubcategoryRules(
                merchants: [
                    'eventim', 'ticketmaster', 'reservix', 'adticket', 'cinemaxx', 'cineplex',
                    'cinestar', 'uci kinowelt', 'kinopolis', 'staatstheater', 'staatsoper',
                    'philharmonie', 'elbphilharmonie', 'deutsches museum', 'pinakothek',
                ],
                keywords: [
                    'theater', 'oper', 'operette', 'konzert', 'konzertkarte', 'museum', 'museumsbesuch',
                    'ausstellung', 'galerie', 'kunsthalle', 'vernissage', 'kino', 'kinokarte',
                    'festival', 'kulturticket', 'schauspielhaus', 'musical',
                ],
            ),

            'Medien' => new SubcategoryRules(
                merchants: [
                    'audible', 'kindle', 'thalia', 'hugendubel', 'osiander', 'buchhandlung',
                    'spiegel verlag', 'zeit verlag', 'faz verlag', 'süddeutsche zeitung',
                    'handelsblatt', 'blinkist', 'readly', 'bookbeat', 'medimops',
                ],
                keywords: [
                    'zeitungsabo', 'zeitschriftenabo', 'digitalabo', 'magazinabo', 'verlagsabo',
                    'zeitungsabonnement', 'buchkauf', 'e-book', 'hörbuch', 'pressekauf',
                    'zeitschrift', 'tageszeitung',
                ],
            ),

            'Sport' => new SubcategoryRules(
                merchants: [
                    'mcfit', 'fitx', 'clever fit', 'john reed', 'urban sports club', 'fitness first',
                    'easyfitness', 'ai fitness', 'decathlon', 'sportscheck', 'intersport',
                    'engelhorn sports', 'keller sports',
                ],
                keywords: [
                    'RAW:\bsport(?!wett)\w*', 'fitnessstudio', 'fitnessbeitrag', 'schwimmbad',
                    'hallenbad', 'sportkurs', 'trainingslager', 'sportausrüstung', 'laufveranstaltung',
                    'startgebühr', 'yogastudio', 'tennisplatz', 'reitstunde',
                ],
            ),

            'Streaming' => new SubcategoryRules(
                merchants: [
                    'netflix', 'spotify', 'RAW:disney\s?\+', 'disney plus', 'prime video',
                    'amazon video', 'dazn', 'sky deutschland', 'wow tv', 'apple music', 'apple tv',
                    'youtube premium', 'deezer', 'RAW:paramount\s?\+', 'joyn plus', 'crunchyroll',
                    'magenta tv', 'RAW:\brtl\s?\+', 'waipu', 'tidal',
                ],
                keywords: [
                    'streamingabo', 'streaming abo', 'videoabo', 'musikabo', 'premium abo',
                    'monatsabo streaming', 'streamingdienst',
                ],
                amountGuard: SubcategoryRules::GUARD_EXPENSE,
            ),

            'Urlaub' => new SubcategoryRules(
                merchants: [
                    'booking.com', 'airbnb', 'hrs hotel', 'expedia', 'trivago', 'hotels.com',
                    'tui', 'dertour', 'fti touristik', 'alltours', 'schauinsland-reisen',
                    'lufthansa', 'eurowings', 'ryanair', 'easyjet', 'condor', 'swiss air', 'klm',
                    'sunexpress', 'reisebüro', 'center parcs', 'landal',
                ],
                keywords: [
                    'RAW:\burlaub(?!sgeld)\w*', 'pauschalreise', 'reisebuchung', 'flugbuchung',
                    'hotelbuchung', 'hotelrechnung', 'übernachtung', 'ferienwohnung', 'ferienhaus',
                    'campingplatz', 'stellplatz', 'reiserücktritt', 'kurtaxe', 'mietwagen',
                    'reisekosten', 'flugticket',
                ],
            ),

            'Vereine' => new SubcategoryRules(
                keywords: [
                    'mitgliedsbeitrag', 'mitgliederbeitrag', 'vereinsbeitrag', 'jahresbeitrag verein',
                    'monatsbeitrag verein', 'sportverein', 'schützenverein', 'förderverein',
                    'kleingartenverein', 'dlrg', 'feuerwehrverein', 'chorbeitrag', 'vereinsabgabe',
                    'mitgliedschaft e.v.',
                ],
                additionalRules: [self::clubMembershipRule()],
            ),
        ];
    }

    /**
     * Zahlungsempfänger ist ein Verein UND der Verwendungszweck nennt einen Beitrag.
     * Ein reiner "e.V."-Treffer wäre zu breit, die Kombination ist treffsicher.
     */
    private static function clubMembershipRule(): RuleDefinition
    {
        return RuleDefinition::allOf(
            self::keyPrefix('Vereine') . '.beitrag-an-verein',
            CriterionDefinition::regex(
                TransactionCategoryCriterion::FIELD_PAYER,
                '/(*UCP)(?:\bverein\w*|\be\.\s?v\.?(?:\s|$))/iu',
            ),
            CriterionDefinition::regex(
                TransactionCategoryCriterion::FIELD_PURPOSE,
                '/(*UCP)\b\w*beitrag\w*\b/iu',
            ),
        );
    }
}
