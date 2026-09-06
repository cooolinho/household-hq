<?php

namespace App\Models\Enums;

use Filament\Support\Icons\Heroicon;

enum GoalIconEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case TROPHY;
    case BANKNOTES;
    case HOME;
    case TRUCK;
    case ACADEMIC_CAP;
    case GLOBE_ALT;
    case CREDIT_CARD;
    case SCALE;
    case DEVICE_PHONE_MOBILE;
    case COMPUTER_DESKTOP;
    case HEART;
    case GIFT;
    case SPARKLES;
    case BUILDING_OFFICE_2;
    case WRENCH_SCREWDRIVER;
    case PAINT_BRUSH;
    case MUSICAL_NOTE;
    case ROCKET_LAUNCH;
    case SHIELD_CHECK;
    case CHART_BAR;

    public static function default(): string
    {
        return self::TROPHY->name;
    }

    /**
     * Case-Name => Heroicon, z. B. für die Icons der Filament-ToggleButtons.
     *
     * @return array<string, Heroicon>
     */
    public static function iconMap(): array
    {
        return array_combine(
            array_map(static fn(self $case): string => $case->name, self::cases()),
            array_map(static fn(self $case): Heroicon => $case->icon(), self::cases()),
        );
    }

    public function icon(): Heroicon
    {
        return match ($this) {
            self::TROPHY => Heroicon::OutlinedTrophy,
            self::BANKNOTES => Heroicon::OutlinedBanknotes,
            self::HOME => Heroicon::OutlinedHome,
            self::TRUCK => Heroicon::OutlinedTruck,
            self::ACADEMIC_CAP => Heroicon::OutlinedAcademicCap,
            self::GLOBE_ALT => Heroicon::OutlinedGlobeAlt,
            self::CREDIT_CARD => Heroicon::OutlinedCreditCard,
            self::SCALE => Heroicon::OutlinedScale,
            self::DEVICE_PHONE_MOBILE => Heroicon::OutlinedDevicePhoneMobile,
            self::COMPUTER_DESKTOP => Heroicon::OutlinedComputerDesktop,
            self::HEART => Heroicon::OutlinedHeart,
            self::GIFT => Heroicon::OutlinedGift,
            self::SPARKLES => Heroicon::OutlinedSparkles,
            self::BUILDING_OFFICE_2 => Heroicon::OutlinedBuildingOffice2,
            self::WRENCH_SCREWDRIVER => Heroicon::OutlinedWrenchScrewdriver,
            self::PAINT_BRUSH => Heroicon::OutlinedPaintBrush,
            self::MUSICAL_NOTE => Heroicon::OutlinedMusicalNote,
            self::ROCKET_LAUNCH => Heroicon::OutlinedRocketLaunch,
            self::SHIELD_CHECK => Heroicon::OutlinedShieldCheck,
            self::CHART_BAR => Heroicon::OutlinedChartBar,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::TROPHY => 'Erfolg',
            self::BANKNOTES => 'Ersparnisse',
            self::HOME => 'Wohnen',
            self::TRUCK => 'Fahrzeug',
            self::ACADEMIC_CAP => 'Bildung',
            self::GLOBE_ALT => 'Reise',
            self::CREDIT_CARD => 'Kredit',
            self::SCALE => 'Abzahlung',
            self::DEVICE_PHONE_MOBILE => 'Handy & Technik',
            self::COMPUTER_DESKTOP => 'Elektronik',
            self::HEART => 'Gesundheit',
            self::GIFT => 'Geschenk',
            self::SPARKLES => 'Besonderes',
            self::BUILDING_OFFICE_2 => 'Immobilie',
            self::WRENCH_SCREWDRIVER => 'Renovierung',
            self::PAINT_BRUSH => 'Hobby',
            self::MUSICAL_NOTE => 'Musik',
            self::ROCKET_LAUNCH => 'Großes Vorhaben',
            self::SHIELD_CHECK => 'Rücklage',
            self::CHART_BAR => 'Allgemein',
        };
    }

    /**
     * Vollständiger Blade-Icon-Name, z. B. "heroicon-o-trophy".
     */
    public function iconName(): string
    {
        return 'heroicon-' . $this->icon()->value;
    }
}
