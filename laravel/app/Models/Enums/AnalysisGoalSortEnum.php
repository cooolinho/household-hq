<?php

namespace App\Models\Enums;

/**
 * Sortierungen für das Sparziele-Modul einer Auswertungs-Karte.
 */
enum AnalysisGoalSortEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case NAME_ASC;
    case NAME_DESC;
    case DATE_ASC;
    case DATE_DESC;
    case PERCENT_ASC;
    case PERCENT_DESC;
    case AMOUNT_ASC;
    case AMOUNT_DESC;

    public static function default(): string
    {
        return self::NAME_ASC->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::NAME_ASC => 'Name aufsteigend',
            self::NAME_DESC => 'Name absteigend',
            self::DATE_ASC => 'Erstellungsdatum aufsteigend',
            self::DATE_DESC => 'Erstellungsdatum absteigend',
            self::PERCENT_ASC => 'Prozent aufsteigend',
            self::PERCENT_DESC => 'Prozent absteigend',
            self::AMOUNT_ASC => 'Summe aufsteigend',
            self::AMOUNT_DESC => 'Summe absteigend',
        };
    }
}
