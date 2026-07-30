<?php

namespace App\Filament\Admin\Resources\Tags\Schemas;

use App\Models\Tag;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TagInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(Tag::name)
                    ->placeholder('-'),
                TextEntry::make(Tag::slug)
                    ->placeholder('-'),
                TextEntry::make(Tag::type)
                    ->placeholder('-'),
                TextEntry::make(Tag::order_column)
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make(Tag::created_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(Tag::updated_at)
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
