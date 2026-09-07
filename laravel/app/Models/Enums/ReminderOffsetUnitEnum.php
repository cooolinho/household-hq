<?php

namespace App\Models\Enums;

use Carbon\CarbonImmutable;

enum ReminderOffsetUnitEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case HOUR;
    case DAY;
    case WEEK;
    case MONTH;

    public function label(): string
    {
        return match ($this) {
            self::HOUR => 'Stunden',
            self::DAY => 'Tage',
            self::WEEK => 'Wochen',
            self::MONTH => 'Monate',
        };
    }

    public function subtract(CarbonImmutable $date, int $value): CarbonImmutable
    {
        return match ($this) {
            self::HOUR => $date->subHours($value),
            self::DAY => $date->subDays($value),
            self::WEEK => $date->subWeeks($value),
            self::MONTH => $date->subMonths($value),
        };
    }
}
