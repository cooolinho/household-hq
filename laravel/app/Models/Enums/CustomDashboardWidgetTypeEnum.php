<?php

namespace App\Models\Enums;

enum CustomDashboardWidgetTypeEnum: string
{
    case CHART = 'chart';
    case STAT = 'stat';
    case TABLE = 'table';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_map(fn(self $case): string => $case->value, self::cases()),
            array_map(fn(self $case): string => $case->label(), self::cases()),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::CHART => 'Chart',
            self::STAT => 'Statistik',
            self::TABLE => 'Tabelle',
        };
    }
}
