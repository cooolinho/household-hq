<?php

namespace App\Models\Enums;

enum EnergyTrackerContractBasePriceIntervalEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case MONTHLY;
    case YEARLY;

    public static function default(): string
    {
        return self::MONTHLY->name;
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
            self::MONTHLY => 'Monatlich',
            self::YEARLY => 'Jährlich',
        };
    }
}
