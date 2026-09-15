<?php

namespace App\Models\Enums;

/**
 * Intervall-Filter für das Budgets-Modul einer Auswertungs-Karte.
 *
 * ALL = alle Transaktionen seit Budget-Erstellung; sonst aktueller Zeitraum.
 */
enum AnalysisBudgetIntervalEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case MONTHLY;
    case QUARTERLY;
    case YEARLY;
    case ALL;

    public static function default(): string
    {
        return self::MONTHLY->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monatlich',
            self::QUARTERLY => 'Quartalsweise',
            self::YEARLY => 'Jährlich',
            self::ALL => 'Alle',
        };
    }
}
