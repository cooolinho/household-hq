<?php

namespace App\Models\Enums;

enum EnergyTrackerCountingMethodEnum
{
    use UseEnumOptionsTrait;

    case ASCENDING;
    case DESCENDING;
    case FLUCTUATING;

    public static function default(): string
    {
        return self::ASCENDING->name;
    }

    public static function tryFromName(?string $state): ?self
    {
        if (blank($state)) {
            return null;
        }

        $name = strtoupper($state);

        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }

    public function label(): string
    {
        return match ($this) {
            self::ASCENDING => 'Aufsteigend',
            self::DESCENDING => 'Absteigend',
            self::FLUCTUATING => 'Schwankend',
        };
    }
}
