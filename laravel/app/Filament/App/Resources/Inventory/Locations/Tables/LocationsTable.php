<?php

namespace App\Filament\App\Resources\Inventory\Locations\Tables;

use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(Location::belongs_to_collection . '.' . Collection::name)
                    ->searchable(),
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
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
