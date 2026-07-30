<?php

namespace App\Models\Enums;

enum InsuranceMoveNotificationChannelEnum
{
    use UseEnumOptionsTrait;

    case EMAIL;
    case BRIEF;

    public static function default(): string
    {
        return self::EMAIL->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => 'E-Mail',
            self::BRIEF => 'Brief',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EMAIL => 'info',
            self::BRIEF => 'warning',
        };
    }
}

