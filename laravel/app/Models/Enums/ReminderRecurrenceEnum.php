<?php

namespace App\Models\Enums;

enum ReminderRecurrenceEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case DAILY;
    case EVERY_N_DAYS;
    case WEEKLY;
    case EVERY_N_WEEKS;
    case MONTHLY_ON_DAY;
    case EVERY_N_MONTHS;
    case QUARTERLY;
    case HALF_YEARLY;
    case YEARLY;
    case ONCE;

    public static function default(): string
    {
        return self::MONTHLY_ON_DAY->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Jeden Tag',
            self::EVERY_N_DAYS => 'Alle X Tage',
            self::WEEKLY => 'Jede Woche',
            self::EVERY_N_WEEKS => 'Alle X Wochen',
            self::MONTHLY_ON_DAY => 'Am X. eines jeden Monats',
            self::EVERY_N_MONTHS => 'Alle X Monate',
            self::QUARTERLY => 'Quartalsweise',
            self::HALF_YEARLY => 'Halbjährlich',
            self::YEARLY => 'Jährlich',
            self::ONCE => 'Nur an einem bestimmten Tag',
        };
    }

    /**
     * Ob diese Wiederholung einen numerischen Zusatzwert (recurrence_value) benötigt.
     */
    public function requiresValue(): bool
    {
        return match ($this) {
            self::EVERY_N_DAYS, self::EVERY_N_WEEKS, self::EVERY_N_MONTHS, self::MONTHLY_ON_DAY => true,
            default => false,
        };
    }

    /**
     * Ob diese Wiederholung ein Startdatum (start_date) benötigt.
     */
    public function requiresStartDate(): bool
    {
        return $this === self::ONCE;
    }

    public function valueLabel(): string
    {
        return match ($this) {
            self::EVERY_N_DAYS => 'Alle X Tage',
            self::EVERY_N_WEEKS => 'Alle X Wochen',
            self::EVERY_N_MONTHS => 'Alle X Monate',
            self::MONTHLY_ON_DAY => 'Tag im Monat',
            default => 'Wert',
        };
    }
}
