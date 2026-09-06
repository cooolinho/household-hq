<?php

namespace App\Models\Enums;

use Filament\Support\Icons\Heroicon;

enum BudgetStatusEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case OK;
    case WARNING;
    case EXCEEDED;

    public static function default(): string
    {
        return self::OK->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::OK => 'Im Rahmen',
            self::WARNING => 'Limit fast erreicht',
            self::EXCEEDED => 'Limit überschritten',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OK => 'success',
            self::WARNING => 'warning',
            self::EXCEEDED => 'danger',
        };
    }

    public function icon(): Heroicon
    {
        return match ($this) {
            self::OK => Heroicon::OutlinedCheckCircle,
            self::WARNING => Heroicon::OutlinedExclamationTriangle,
            self::EXCEEDED => Heroicon::OutlinedExclamationCircle,
        };
    }

    /**
     * Rangfolge für den Eskalationsvergleich der Benachrichtigungen.
     */
    public function severity(): int
    {
        return match ($this) {
            self::OK => 0,
            self::WARNING => 1,
            self::EXCEEDED => 2,
        };
    }
}
