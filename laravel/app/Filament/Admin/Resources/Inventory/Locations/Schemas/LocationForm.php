<?php

namespace App\Filament\Admin\Resources\Inventory\Locations\Schemas;

use App\Models\Inventory\Location;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make(Location::collection_id)
                    ->relationship(Location::belongs_to_collection, Location::name)
                    ->required(),
                TextInput::make(Location::name)
                    ->required(),
                Textarea::make(Location::description)
                    ->columnSpanFull(),
                FileUpload::make(Location::preview_image)
                    ->image(),
            ]);
    }
}
