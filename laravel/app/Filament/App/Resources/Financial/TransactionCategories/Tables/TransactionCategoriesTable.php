<?php

namespace App\Filament\App\Resources\Financial\TransactionCategories\Tables;

use App\Filament\App\Resources\Financial\TransactionCategories\Actions\CreateSubcategoryAction;
use App\Filament\App\Resources\Financial\TransactionCategories\Actions\MoveCategoryAction;
use App\Filament\App\Resources\Financial\TransactionCategories\Actions\ViewCategoryTransactionsAction;
use App\Models\Financial\TransactionCategory;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
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
                    ->sortable(),

                TextColumn::make('transactions_count')
                    ->label('Transaktionen')
                    ->state(fn(TransactionCategory $record): int => $record->getTransactionCountIncludingDescendants((int)auth()->id()))
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make(TransactionCategory::belongs_to_parent . '.' . TransactionCategory::name)
                    ->label('Hauptkategorie')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make(TransactionCategory::has_many_children . '_count')
                    ->label('Unterkategorien')
                    ->state(function (TransactionCategory $record): int {
                        $descendantIds = $record->getDescendantIds();

                        if ($descendantIds === []) {
                            return 0;
                        }

                        return TransactionCategory::query()
                            ->visibleForUser((int)auth()->id())
                            ->whereIn(TransactionCategory::id, $descendantIds)
                            ->count();
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make(TransactionCategory::has_many_rules . '_count')
                    ->label('Regeln')
                    ->state(function (TransactionCategory $record): int {
                        $query = $record->rules();

                        if ($record->isGlobal()) {
                            $query->where(function ($query) {
                                $query->whereNull(TransactionCategory::user_id)
                                    ->orWhere(TransactionCategory::user_id, auth()->id());
                            });
                        }

                        return $query->count();
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make(TransactionCategory::user_id)
                    ->label('Quelle')
                    ->badge()
                    ->formatStateUsing(fn(?int $state): string => $state === null ? 'Global' : 'Eigene')
                    ->colors([
                        'primary' => static fn(?int $state): bool => $state === null,
                        'success' => static fn(?int $state): bool => $state !== null,
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make(TransactionCategory::active)
                    ->label('Aktiv')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        ->visibleForUser((int)auth()->id())
                        ->orderBy(TransactionCategory::name)
                        ->pluck(TransactionCategory::name, TransactionCategory::id)
                        ->toArray())
                    ->searchable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->visible(fn(TransactionCategory $record): bool => !$record->isGlobal()),
                    ViewCategoryTransactionsAction::make(),
                    CreateSubcategoryAction::make(),
                    MoveCategoryAction::make(),
                ]),
            ]);
    }
}
