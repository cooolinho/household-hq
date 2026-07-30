<?php

namespace App\Menu;

use BackedEnum;
use Filament\Support\Contracts\Collapsible;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum NavigationGroup implements HasIcon, HasLabel, Collapsible
{
    case INSURANCES;
    case FIXED_COSTS;
    case BANKS;
    case ENERGY_TRACKER;
    case INVENTORY;
    case FEATURES;

    public function getLabel(): string
    {
        return match ($this) {
            self::INSURANCES => __('Versicherungen'),
            self::BANKS => __('Banken'),
            self::FIXED_COSTS => __('Fixkosten'),
            self::ENERGY_TRACKER => __('Energy Tracker'),
            self::INVENTORY => __('Inventar'),
            self::FEATURES => __('Features'),
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::INSURANCES => Heroicon::OutlinedCurrencyDollar,
            self::BANKS => Heroicon::OutlinedBanknotes,
            self::FIXED_COSTS => Heroicon::OutlinedCreditCard,
            self::ENERGY_TRACKER => Heroicon::OutlinedBolt,
            self::INVENTORY => Heroicon::OutlinedArchiveBox,
            self::FEATURES => Heroicon::OutlinedPuzzlePiece,
        };
    }

    public function isCollapsible(): bool
    {
        return true;
    }

    public function isCollapsed(): bool
    {
        return true;
    }
}
