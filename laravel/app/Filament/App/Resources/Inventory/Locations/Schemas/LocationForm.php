<?php

namespace App\Filament\App\Resources\Inventory\Locations\Schemas;

use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema, ?int $collectionId = null): Schema
    {
        return $schema
            ->components([
                Section::make('Standort')
                    ->description('Lege den Standort innerhalb einer Collection an.')
                    ->schema(static::components($collectionId))
                    ->columns(2),
            ]);
    }

    public static function components(?int $collectionId = null): array
    {
        return [
            Select::make(Location::collection_id)
                ->label('Collection')
                ->relationship(
                    Location::belongs_to_collection,
                    Collection::name,
                    modifyQueryUsing: fn($query) => $query->where(Collection::user_id, auth()->id())
                )
                ->default($collectionId)
                ->required()
                ->disabled($collectionId !== null)
                ->dehydrated(),
            TextInput::make(Location::name)
                ->label('Name')
                ->required()
                ->maxLength(255),
            Textarea::make(Location::description)
                ->label('Beschreibung')
                ->rows(3)
                ->maxLength(1000)
                ->columnSpanFull(),
            FileUpload::make(Location::preview_image)
                ->label('Vorschaubild')
                ->disk(Location::STORAGE_DISK)
                ->image()
                ->columnSpanFull(),
        ];
    }
}
