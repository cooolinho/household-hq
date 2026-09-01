<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Tables;

use App\Models\Financial\TransactionCategory;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
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
                    ->color('info'),

                BadgeColumn::make(TransactionCategory::user_id)
                    ->label('Quelle')
                    ->formatStateUsing(fn(?int $state): string => $state === null ? 'Global' : 'Eigene')
                    ->colors([
                        'primary' => static fn(?int $state): bool => $state === null,
                        'success' => static fn(?int $state): bool => $state !== null,
                    ]),

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
                        ->visibleForUser((int)auth()->id())
                        ->whereNull(TransactionCategory::parent_id)
                        ->orderBy(TransactionCategory::name)
                        ->pluck(TransactionCategory::name, TransactionCategory::id)
                        ->toArray())
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
