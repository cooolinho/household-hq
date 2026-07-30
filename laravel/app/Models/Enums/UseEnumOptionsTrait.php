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

    public static function allNames(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function allValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
