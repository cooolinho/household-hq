<?php

namespace App\Models\Enums;

enum EnergyTrackerCountingTypeEnum
{
    use UseEnumOptionsTrait;

    case GAS;
    case HEIZUNG;
    case KALTWASSER;
    case STROM;
    case WARMWASSER;

    public static function from($state)
    {
        return match (strtoupper((string)$state)) {
            'GAS' => self::GAS,
            'HEIZUNG' => self::HEIZUNG,
            'KALTWASSER' => self::KALTWASSER,
            'STROM' => self::STROM,
            'WARMWASSER' => self::WARMWASSER,
            default => throw new \InvalidArgumentException("Invalid state: $state"),
        };
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

    public static function default(): string
    {
        return self::STROM->name;
    }

    public function label(): string
    {
        return match ($this) {
            self::GAS => 'Gas',
            self::HEIZUNG => 'Heizung',
            self::KALTWASSER => 'Kaltwasser',
            self::STROM => 'Strom',
            self::WARMWASSER => 'Warmwasser',
        };
    }

    public function defaultUnit(): EnergyTrackerUnitEnum
    {
        return match ($this) {
            self::GAS => EnergyTrackerUnitEnum::M3,
            self::HEIZUNG => EnergyTrackerUnitEnum::H,
            self::KALTWASSER => EnergyTrackerUnitEnum::M3,
            self::STROM => EnergyTrackerUnitEnum::KWH,
            self::WARMWASSER => EnergyTrackerUnitEnum::M3,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::GAS => 'heroicon-o-fire',
            self::HEIZUNG => 'heroicon-o-home-modern',
            self::KALTWASSER => 'heroicon-o-beaker',
            self::STROM => 'heroicon-o-bolt',
            self::WARMWASSER => 'heroicon-o-beaker',
        };
    }
}
