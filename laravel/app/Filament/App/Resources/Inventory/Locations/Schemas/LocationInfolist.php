<?php

namespace App\Filament\App\Resources\Inventory\Locations\Schemas;

use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LocationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(Location::belongs_to_collection . '.' . Collection::name)
                    ->label('Collection'),
                TextEntry::make(Location::name),
                TextEntry::make(Location::description)
                    ->placeholder('-')
                    ->columnSpanFull(),
                ImageEntry::make(Location::preview_image)
                    ->getStateUsing(fn(Location $record): ?string => filled($record->{Location::preview_image})
                        ? route('app.inventory.preview', ['type' => 'location', 'record' => $record->getKey()])
                        : null)
                    ->defaultImageUrl(asset('location.jpg')),
                TextEntry::make(Location::created_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(Location::updated_at)
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
