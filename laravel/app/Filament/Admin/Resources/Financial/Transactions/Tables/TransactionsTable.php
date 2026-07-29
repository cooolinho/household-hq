<?php

namespace App\Filament\Admin\Resources\Financial\Transactions\Tables;

use App\Filament\Admin\Resources\Financial\Transactions\Actions\CreateFixedCostAction;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(Transaction::date)
                    ->date()
                    ->sortable(),
                TextColumn::make(Transaction::value_date)
                    ->date()
                    ->sortable(),
                TextColumn::make(Transaction::payer)
                    ->searchable(),
                TextColumn::make(Transaction::description)
                    ->searchable(),
                TextColumn::make(Transaction::purpose)
                    ->searchable(),
                TextColumn::make(Transaction::balance)
                    ->numeric()
                    ->sortable(),
                TextColumn::make(Transaction::balance_currency)
                    ->searchable(),
                TextColumn::make(Transaction::amount)
                    ->numeric()
                    ->sortable(),
                TextColumn::make(Transaction::amount_currency)
                    ->searchable(),
                // Fixkosten-Verknüpfung
                TextColumn::make(Transaction::belongs_to_fixed_cost . '.' . FixedCost::name)
                    ->label('Fixkosten')
                    ->placeholder('—')
                    ->badge()
                    ->color('success')
                    ->searchable(),
                IconColumn::make(Transaction::fixed_cost_id)
                    ->label('Verknüpft')
                    ->boolean()
                    ->trueIcon('heroicon-o-link')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Transaction::created_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Transaction::updated_at)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    CreateFixedCostAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
