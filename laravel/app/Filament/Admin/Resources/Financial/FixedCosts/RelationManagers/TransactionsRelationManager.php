<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\RelationManagers;

use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = FixedCost::has_many_transactions;

    protected static ?string $title = 'Verknüpfte Transaktionen';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(Transaction::purpose)
            ->columns([
                TextColumn::make(Transaction::date)
                    ->label('Datum')
                    ->date()
                    ->sortable(),
                TextColumn::make(Transaction::payer)
                    ->label('Auftraggeber')
                    ->searchable(),
                TextColumn::make(Transaction::purpose)
                    ->label('Verwendungszweck')
                    ->searchable()
                    ->limit(50),
                TextColumn::make(Transaction::amount)
                    ->label('Betrag')
                    ->numeric()
                    ->sortable()
                    ->color(fn(Transaction $record) => $record->amount >= 0 ? 'success' : 'danger'),
                TextColumn::make(Transaction::amount_currency)
                    ->label('Währung'),
                TextColumn::make(Transaction::date)
                    ->label('Wertstellung')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                AssociateAction::make()
                    ->label('Transaktion zuordnen')
                    ->recordSelectOptionsQuery(fn(Builder $query) => $query
                        ->where(Transaction::user_id, auth()->id())
                        ->whereNull(Transaction::fixed_cost_id)
                    )
                    ->recordSelectSearchColumns([
                        Transaction::payer,
                        Transaction::purpose,
                        Transaction::description,
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                DissociateAction::make()
                    ->label('Entkoppeln'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make()
                        ->label('Auswahl entkoppeln'),
                ]),
            ]);
    }
}

