<?php

namespace App\Models\Enums;

enum EnergyTrackerUnitEnum
{
    use UseEnumOptionsTrait;

    case M3; // Wasser
    case H; // Heizung
    case KWH; // Kilowattstunden

    public static function default(): string
    {
        return self::KWH->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::M3 => 'm³',
            self::H => 'H',
            self::KWH => 'kWh',
        };
    }
}
