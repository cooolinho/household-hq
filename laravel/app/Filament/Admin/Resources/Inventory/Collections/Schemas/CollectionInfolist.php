<?php

namespace App\Filament\Admin\Resources\Inventory\Collections\Schemas;

use App\Models\Inventory\Collection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CollectionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(Collection::name),
                TextEntry::make(Collection::created_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(Collection::updated_at)
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
