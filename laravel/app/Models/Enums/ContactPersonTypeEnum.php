<?php

namespace App\Models\Enums;

enum ContactPersonTypeEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case PRIVATE;
    case BUSINESS;

    public static function default(): string
    {
        return self::PRIVATE->name;
    }

    public static function tryFromName(?string $state): ?self
    {
        if (blank($state)) {
            return null;
        }

        return self::tryFrom(strtoupper($state));
    }

    public function label(): string
    {
        return match ($this) {
            self::PRIVATE => 'Privat',
            self::BUSINESS => 'Geschäftlich',
        };
    }
}
