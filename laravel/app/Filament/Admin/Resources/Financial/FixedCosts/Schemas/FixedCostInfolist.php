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
                TextEntry::make('category.name')
                    ->label('Kategorie')
                    ->badge()
                    ->placeholder('Nicht kategorisiert'),
                TextEntry::make(FixedCost::interval)
                    ->badge(),
                TextEntry::make(FixedCost::ends_mode)
                    ->badge(),
                TextEntry::make(FixedCost::ends_date)
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(FixedCost::extended_date)
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(FixedCost::extended_interval)
                    ->badge(),
                TextEntry::make(FixedCost::next_booking_date)
                    ->label('Nächste Buchung am')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(FixedCost::created_at)
                    ->label('Erstellt am')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(FixedCost::updated_at)
                    ->label('Zuletzt aktualisiert am')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
