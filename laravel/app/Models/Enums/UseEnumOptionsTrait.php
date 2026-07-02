<?php

namespace App\Models\Enums;

trait UseEnumOptionsTrait
{
    public static function options(): array
    {
        return array_combine(
            array_map(fn($case) => $case->name, self::cases()),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
