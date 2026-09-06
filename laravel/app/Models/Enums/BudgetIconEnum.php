<?php

namespace App\Models\Enums;

use Filament\Support\Icons\Heroicon;

enum BudgetIconEnum
{
    use UseEnumOptionsTrait;
    use UseTryFromTrait;

    case SHOPPING_CART;
    case SHOPPING_BAG;
    case BUILDING_STOREFRONT;
    case CAKE;
    case HOME;
    case TRUCK;
    case BOLT;
    case FIRE;
    case HEART;
    case SPARKLES;
    case FILM;
    case MUSICAL_NOTE;
    case TICKET;
    case TROPHY;
    case GIFT;
    case GLOBE_ALT;
    case ACADEMIC_CAP;
    case DEVICE_PHONE_MOBILE;
    case WRENCH_SCREWDRIVER;
    case PAINT_BRUSH;
    case BRIEFCASE;
    case USERS;
    case BANKNOTES;
    case CREDIT_CARD;
    case RECEIPT_PERCENT;
    case CHART_PIE;

    public static function default(): string
    {
        return self::SHOPPING_CART->name;
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
            self::SHOPPING_CART => Heroicon::OutlinedShoppingCart,
            self::SHOPPING_BAG => Heroicon::OutlinedShoppingBag,
            self::BUILDING_STOREFRONT => Heroicon::OutlinedBuildingStorefront,
            self::CAKE => Heroicon::OutlinedCake,
            self::HOME => Heroicon::OutlinedHome,
            self::TRUCK => Heroicon::OutlinedTruck,
            self::BOLT => Heroicon::OutlinedBolt,
            self::FIRE => Heroicon::OutlinedFire,
            self::HEART => Heroicon::OutlinedHeart,
            self::SPARKLES => Heroicon::OutlinedSparkles,
            self::FILM => Heroicon::OutlinedFilm,
            self::MUSICAL_NOTE => Heroicon::OutlinedMusicalNote,
            self::TICKET => Heroicon::OutlinedTicket,
            self::TROPHY => Heroicon::OutlinedTrophy,
            self::GIFT => Heroicon::OutlinedGift,
            self::GLOBE_ALT => Heroicon::OutlinedGlobeAlt,
            self::ACADEMIC_CAP => Heroicon::OutlinedAcademicCap,
            self::DEVICE_PHONE_MOBILE => Heroicon::OutlinedDevicePhoneMobile,
            self::WRENCH_SCREWDRIVER => Heroicon::OutlinedWrenchScrewdriver,
            self::PAINT_BRUSH => Heroicon::OutlinedPaintBrush,
            self::BRIEFCASE => Heroicon::OutlinedBriefcase,
            self::USERS => Heroicon::OutlinedUsers,
            self::BANKNOTES => Heroicon::OutlinedBanknotes,
            self::CREDIT_CARD => Heroicon::OutlinedCreditCard,
            self::RECEIPT_PERCENT => Heroicon::OutlinedReceiptPercent,
            self::CHART_PIE => Heroicon::OutlinedChartPie,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SHOPPING_CART => 'Einkaufswagen',
            self::SHOPPING_BAG => 'Einkaufstasche',
            self::BUILDING_STOREFRONT => 'Geschäft',
            self::CAKE => 'Essen & Trinken',
            self::HOME => 'Wohnen',
            self::TRUCK => 'Mobilität',
            self::BOLT => 'Energie',
            self::FIRE => 'Genussmittel',
            self::HEART => 'Gesundheit',
            self::SPARKLES => 'Körperpflege',
            self::FILM => 'Unterhaltung',
            self::MUSICAL_NOTE => 'Musik',
            self::TICKET => 'Veranstaltungen',
            self::TROPHY => 'Sport',
            self::GIFT => 'Geschenke',
            self::GLOBE_ALT => 'Urlaub',
            self::ACADEMIC_CAP => 'Bildung',
            self::DEVICE_PHONE_MOBILE => 'Handy & Internet',
            self::WRENCH_SCREWDRIVER => 'Reparatur & Baumarkt',
            self::PAINT_BRUSH => 'Hobby',
            self::BRIEFCASE => 'Beruf',
            self::USERS => 'Familie',
            self::BANKNOTES => 'Bargeld',
            self::CREDIT_CARD => 'Karte',
            self::RECEIPT_PERCENT => 'Gebühren',
            self::CHART_PIE => 'Allgemein',
        };
    }

    /**
     * Vollständiger Blade-Icon-Name, z. B. "heroicon-o-shopping-cart".
     */
    public function iconName(): string
    {
        return 'heroicon-' . $this->icon()->value;
    }
}
