<?php

namespace App\Filament\App\Resources\Inventory\Collections\RelationManagers;

use App\Models\Inventory\Location;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'locations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make(Location::name)
                    ->required(),
                Textarea::make(Location::description)
                    ->columnSpanFull(),
                FileUpload::make(Location::preview_image)
                    ->disk(Location::STORAGE_DISK)
                    ->image(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make(Location::name)
                    ->searchable(),
                ImageColumn::make(Location::preview_image)
                    ->getStateUsing(fn(Location $record): ?string => filled($record->{Location::preview_image})
                        ? route('app.inventory.preview', ['type' => 'location', 'record' => $record->getKey()])
                        : null)
                    ->defaultImageUrl(asset('location.jpg')),
                TextColumn::make(Location::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Location::updated_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
