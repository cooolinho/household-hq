<?php

namespace App\Filament\Admin\Resources\Financial\InsuranceCategories\Tables;

use App\Models\Financial\InsuranceCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InsuranceCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(InsuranceCategory::group)
            ->columns([
                TextColumn::make(InsuranceCategory::name)
                    ->label('Name')
                    ->searchable(),
                TextColumn::make(InsuranceCategory::group)
                    ->label('Gruppe')
                    ->badge()
                    ->searchable(),
                TextColumn::make(InsuranceCategory::created_at)
                    ->label('Erstellt')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make(InsuranceCategory::group)
                    ->label('Gruppe')
                    ->options(fn() => InsuranceCategory::query()
                        ->distinct()
                        ->orderBy(InsuranceCategory::group)
                        ->pluck(InsuranceCategory::group, InsuranceCategory::group)
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

