<?php

namespace App\Filament\App\Resources\Financial\TransactionCategories\RelationManagers;

use App\Filament\App\Resources\Financial\Transactions\Schemas\TransactionInfolist;
use App\Filament\App\Resources\Financial\Transactions\Tables\TransactionsTable;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = TransactionCategory::belongs_to_many_transactions;

    protected static ?string $title = 'Verknüpfte Transaktionen';

    public function infolist(Schema $schema): Schema
    {
        return TransactionInfolist::configure($schema);
    }

    public function table(Table $table): Table
    {
        return TransactionsTable::configure($table)
            ->recordTitleAttribute(Transaction::purpose)
            ->headerActions([
                AttachAction::make()
                    ->label('Transaktion zuordnen')
                    ->recordSelectOptionsQuery(fn(Builder $query) => $query
                        ->where(Transaction::user_id, auth()->id())
                    )
                    ->recordSelectSearchColumns([
                        Transaction::payer,
                        Transaction::purpose,
                        Transaction::description,
                    ]),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Entkoppeln'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->label('Auswahl entkoppeln'),
                ]),
            ]);
    }
}
