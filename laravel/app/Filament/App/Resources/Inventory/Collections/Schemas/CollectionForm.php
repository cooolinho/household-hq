<?php

namespace App\Filament\App\Resources\Inventory\Collections\Schemas;

use App\Models\Inventory\Collection;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Collection')
                    ->columns(2)
                    ->schema([
                        TextInput::make(Collection::name)
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        FileUpload::make(Collection::preview_image)
                            ->label('Vorschaubild')
                            ->disk(Collection::STORAGE_DISK)
                            ->image()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
