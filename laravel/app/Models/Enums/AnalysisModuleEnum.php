<?php

namespace App\Models\Enums;

/**
 * Module einer Auswertungs-Karte.
 */
enum AnalysisModuleEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case BUDGETS;
    case SAVINGS_GOALS;
    case INCOME;
    case EXPENSES;
    case DEVELOPMENTS;
    case TAGS;

    public function label(): string
    {
        return match ($this) {
            self::BUDGETS => 'Budgets',
            self::SAVINGS_GOALS => 'Sparziele',
            self::INCOME => 'Einnahmen',
            self::EXPENSES => 'Ausgaben',
            self::DEVELOPMENTS => 'Entwicklungen',
            self::TAGS => 'Tags',
        };
    }
}
