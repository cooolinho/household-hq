<?php

namespace App\Filament\Admin\Resources\Transactions\Tables;

use App\Models\Transaction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
