<?php

namespace App\Filament\Admin\Resources\Financial\FixedCostCategories\Tables;

use App\Models\Financial\FixedCostCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FixedCostCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(FixedCostCategory::group)
            ->columns([
                TextColumn::make(FixedCostCategory::name)
                    ->label('Name')
                    ->searchable(),
                TextColumn::make(FixedCostCategory::group)
                    ->label('Gruppe')
                    ->badge()
                    ->placeholder('Nicht kategorisiert')
                    ->searchable(),
                TextColumn::make(FixedCostCategory::created_at)
                    ->label('Erstellt')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make(FixedCostCategory::group)
                    ->label('Gruppe')
                    ->options(fn() => FixedCostCategory::query()
                        ->whereNotNull(FixedCostCategory::group)
                        ->distinct()
                        ->orderBy(FixedCostCategory::group)
                        ->pluck(FixedCostCategory::group, FixedCostCategory::group)
                        ->toArray())
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

