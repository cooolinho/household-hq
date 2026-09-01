<?php

namespace App\Filament\Admin\Resources\Inventory\Collections\Tables;

use App\Models\Inventory\Collection;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make(Collection::preview_image)
                    ->label('Vorschau')
                    ->getStateUsing(fn(Collection $record): ?string => filled($record->{Collection::preview_image})
                        ? route('admin.inventory.preview', ['type' => 'collection', 'record' => $record->getKey()])
                        : null)
                    ->defaultImageUrl(asset('collection.jpg')),
                TextColumn::make(Collection::name)
                    ->searchable(),
                TextColumn::make(Collection::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Collection::updated_at)
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
