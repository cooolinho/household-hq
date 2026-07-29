<?php

namespace App\Models\Enums;

enum FixedCostEndsModeEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case NONE;
    case ENDS;
    case EXTENDED;

    public static function default(): string
    {
        return self::NONE->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Kein Enddatum',
            self::ENDS => 'Endet',
            self::EXTENDED => 'Verlängert',
        };
    }
}
