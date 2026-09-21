<?php

namespace App\Models\Enums;

/**
 * Zeitraum-Presets für eine Auswertungs-Karte.
 */
enum AnalysisTimeframeEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case MONTHLY;
    case MID_MONTH;
    case QUARTERLY;
    case YEARLY;
    case CUSTOM;

    public static function default(): string
    {
        return self::MONTHLY->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monatlich',
            self::MID_MONTH => 'Monatsmitte',
            self::QUARTERLY => 'Quartalsweise',
            self::YEARLY => 'Jährlich',
            self::CUSTOM => 'Eigene Auswahl',
        };
    }

    public function isCustom(): bool
    {
        return $this === self::CUSTOM;
    }
}
