<?php

namespace App\Models\Enums;

enum InsuranceMoveNotificationStatusEnum
{
    use UseEnumOptionsTrait;

    case PLANNED;
    case SENT;
    case FAILED;

    public static function default(): string
    {
        return self::PLANNED->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Geplant',
            self::SENT => 'Versendet',
            self::FAILED => 'Fehlgeschlagen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PLANNED => 'gray',
            self::SENT => 'success',
            self::FAILED => 'danger',
        };
    }
}

