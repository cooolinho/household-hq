<?php

namespace App\Filament\App\Resources\Inventory\Collections\Schemas;

use App\Models\Inventory\Collection;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CollectionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(Collection::name),
                ImageEntry::make(Collection::preview_image)
                    ->label('Vorschaubild')
                    ->getStateUsing(fn(Collection $record): ?string => filled($record->{Collection::preview_image})
                        ? route('app.inventory.preview', ['type' => 'collection', 'record' => $record->getKey()])
                        : null)
                    ->defaultImageUrl(asset('collection.jpg')),
                TextEntry::make(Collection::created_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(Collection::updated_at)
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
