<?php

namespace Database\Seeders\Financial\CategoryRules;

use InvalidArgumentException;

/**
 * Baut aus lesbaren Keyword-Listen treffsichere Regex-Pattern mit Wortgrenzen.
 *
 * Beispiel:
 *   KeywordPattern::build(['rewe', 'aldi süd'])
 *   => ['/(*UCP)\b(?:rewe|aldi\ süd)\b/iu']
 *
 * Das Verb (*UCP) ist zwingend: ohne Unicode-Properties basiert \b auf ASCII-\w,
 * wodurch Keywords mit Umlaut/Akzent am Anfang oder Ende (z.B. "Öl", "Café")
 * niemals matchen würden.
 *
 * Keywords mit dem Präfix self::RAW_PREFIX werden ungequotet übernommen und
 * ohne umschließende Wortgrenzen eingesetzt – für negative Lookaheads oder
 * selbst gesetzte Grenzen (z.B. 'RAW:kredit(?!karte)').
 */
final class KeywordPattern
{
    public const string RAW_PREFIX = 'RAW:';

    /**
     * Maximale Länge eines erzeugten Patterns. Längere Keyword-Listen werden auf
     * mehrere Pattern verteilt (innerhalb einer OR-Regel bleibt die Semantik gleich).
     */
    private const int MAX_PATTERN_LENGTH = 800;

    /**
     * Ab dieser Länge darf ein Keyword auch als erstes Glied eines Kompositums matchen
     * ("friseur" trifft "Friseursalon", "apotheke" trifft "Apothekenrechnung").
     * Kürzere Keywords bleiben strikt begrenzt, damit z.B. "oper" nicht "Operation" trifft.
     */
    private const int MIN_COMPOUND_LENGTH = 6;

    private const string PREFIX = '/(*UCP)';

    private const string SUFFIX = '/iu';

    /**
     * @param list<string> $keywords
     * @return list<string> ein oder mehrere vollständige Regex-Pattern inkl. Delimiter
     */
    public static function build(array $keywords): array
    {
        $strict = [];
        $compound = [];
        $raw = [];

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);

            if ($keyword === '') {
                continue;
            }

            if (str_starts_with($keyword, self::RAW_PREFIX)) {
                $fragment = trim(substr($keyword, strlen(self::RAW_PREFIX)));

                if ($fragment === '') {
                    continue;
                }

                $raw[] = $fragment;

                continue;
            }

            $keyword = mb_strtolower($keyword);
            $variants = array_map(
                static fn(string $variant): string => preg_quote($variant, '/'),
                [$keyword, ...self::transliterations($keyword)],
            );

            if (mb_strlen($keyword) >= self::MIN_COMPOUND_LENGTH) {
                $compound = [...$compound, ...$variants];

                continue;
            }

            $strict = [...$strict, ...$variants];
        }

        $patterns = [
            ...self::chunk($strict, '\b(?:', ')\b'),
            ...self::chunk($compound, '\b(?:', ')\w*'),
            ...self::chunk($raw, '(?:', ')'),
        ];

        if ($patterns === []) {
            throw new InvalidArgumentException('KeywordPattern::build() benötigt mindestens ein Keyword.');
        }

        return $patterns;
    }

    /**
     * Kontoauszüge transliterieren Umlaute häufig (GASTSTAETTE, MUELLER, STRASSE).
     * Für solche Keywords wird zusätzlich die ASCII-Schreibweise aufgenommen.
     *
     * @return list<string>
     */
    private static function transliterations(string $keyword): array
    {
        $ascii = strtr($keyword, [
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'ß' => 'ss',
            'é' => 'e',
            'è' => 'e',
            'á' => 'a',
            'à' => 'a',
        ]);

        return $ascii === $keyword ? [] : [$ascii];
    }

    /**
     * @param list<string> $fragments
     * @return list<string>
     */
    private static function chunk(array $fragments, string $open, string $close): array
    {
        if ($fragments === []) {
            return [];
        }

        $overhead = strlen(self::PREFIX) + strlen($open) + strlen($close) + strlen(self::SUFFIX);
        $patterns = [];
        $current = [];
        $currentLength = 0;

        foreach ($fragments as $fragment) {
            $additional = strlen($fragment) + ($current === [] ? 0 : 1);

            if ($current !== [] && $overhead + $currentLength + $additional > self::MAX_PATTERN_LENGTH) {
                $patterns[] = self::PREFIX . $open . implode('|', $current) . $close . self::SUFFIX;
                $current = [];
                $currentLength = 0;
                $additional = strlen($fragment);
            }

            $current[] = $fragment;
            $currentLength += $additional;
        }

        if ($current !== []) {
            $patterns[] = self::PREFIX . $open . implode('|', $current) . $close . self::SUFFIX;
        }

        return $patterns;
    }
}
