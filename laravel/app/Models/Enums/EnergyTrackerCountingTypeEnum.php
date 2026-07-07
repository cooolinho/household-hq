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
        return match ($state) {
            'gas' => self::GAS,
            'heizung' => self::HEIZUNG,
            'kaltwasser' => self::KALTWASSER,
            'strom' => self::STROM,
            'warmwasser' => self::WARMWASSER,
            default => throw new \InvalidArgumentException("Invalid state: $state"),
        };
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
}
