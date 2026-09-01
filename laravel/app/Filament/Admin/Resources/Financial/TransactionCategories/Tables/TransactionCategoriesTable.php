<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Tables;

use App\Models\Financial\TransactionCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TransactionCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(TransactionCategory::name)
            ->columns([
                TextColumn::make(TransactionCategory::name)
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make(TransactionCategory::belongs_to_parent . '.' . TransactionCategory::name)
                    ->label('Hauptkategorie')
                    ->badge()
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make(TransactionCategory::has_many_rules . '_count')
                    ->label('Regeln')
                    ->counts(TransactionCategory::has_many_rules)
                    ->badge()
                    ->color('info'),

                IconColumn::make(TransactionCategory::active)
                    ->label('Aktiv')
                    ->boolean(),

                TextColumn::make(TransactionCategory::created_at)
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make(TransactionCategory::active)
                    ->label('Aktiv'),

                SelectFilter::make(TransactionCategory::parent_id)
                    ->label('Hauptkategorie')
                    ->options(fn() => TransactionCategory::query()
                        ->whereNull(TransactionCategory::parent_id)
                        ->where(TransactionCategory::user_id, auth()->id())
                        ->orderBy(TransactionCategory::name)
                        ->pluck(TransactionCategory::name, TransactionCategory::id)
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
