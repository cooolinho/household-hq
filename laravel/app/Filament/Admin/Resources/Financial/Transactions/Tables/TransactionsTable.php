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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(Transaction::date)
                    ->label('Buchung')
                    ->date()
                    ->sortable(),
                TextColumn::make(Transaction::payer)
                    ->label('Auftraggeber')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make(Transaction::purpose)
                    ->label('Verwendungszweck')
                    ->searchable()
                    ->limit(70),
                TextColumn::make(Transaction::amount)
                    ->label('Betrag')
                    ->formatStateUsing(fn(Transaction $record): string => number_format($record->amount, 2, ',', '.') . ' ' . $record->amount_currency)
                    ->sortable()
                    ->alignEnd()
                    ->color(fn(Transaction $record) => $record->amount >= 0 ? 'success' : 'danger'),
                // Fixkosten-Verknüpfung
                TextColumn::make(Transaction::belongs_to_fixed_cost . '.' . FixedCost::name)
                    ->label('Fixkosten')
                    ->placeholder('—')
                    ->badge()
                    ->color('success')
                    ->searchable(),
                TextColumn::make(Transaction::value_date)
                    ->label('Wertstellung')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Transaction::description)
                    ->label('Beschreibung')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Transaction::balance)
                    ->label('Kontostand')
                    ->formatStateUsing(fn(Transaction $record): string => number_format($record->balance, 2, ',', '.') . ' ' . $record->balance_currency)
                    ->sortable()
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
            ->recordAction('view')
            ->recordActions([
                ViewAction::make()
                    ->label('Details')
                    ->modalHeading('Transaktionsdetails')
                    ->modalSubmitAction(false)
                    ->modalWidth('4xl')
                    ->extraModalFooterActions([
                        CreateFixedCostAction::make(),
                    ]),
                ActionGroup::make([
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
