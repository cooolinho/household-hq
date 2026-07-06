<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Schemas;

use App\Models\Financial\FixedCost;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FixedCostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(FixedCost::name),
                TextEntry::make(FixedCost::amount)
                    ->numeric(),
                TextEntry::make(FixedCost::category)
                    ->badge(),
                TextEntry::make(FixedCost::interval)
                    ->badge(),
                TextEntry::make(FixedCost::ends_mode)
                    ->badge(),
                TextEntry::make(FixedCost::ends_date)
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(FixedCost::ends_interval)
                    ->badge(),
                TextEntry::make(FixedCost::extended_date)
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(FixedCost::extended_interval)
                    ->badge(),
                TextEntry::make(FixedCost::created_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(FixedCost::updated_at)
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
