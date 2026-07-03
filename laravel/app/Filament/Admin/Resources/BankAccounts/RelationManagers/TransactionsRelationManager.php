<?php

namespace App\Filament\Admin\Resources\BankAccounts\RelationManagers;

use App\Filament\Admin\Resources\Transactions\Actions\ImportCSVFileAction;
use App\Filament\Admin\Resources\Transactions\Schemas\TransactionForm;
use App\Filament\Admin\Resources\Transactions\Schemas\TransactionInfolist;
use App\Filament\Admin\Resources\Transactions\Tables\TransactionsTable;
use App\Models\BankAccount;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = BankAccount::has_many_transactions;

    public function form(Schema $schema): Schema
    {
        return TransactionForm::configure($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return TransactionInfolist::configure($schema);
    }

    public function table(Table $table): Table
    {
        /** @var BankAccount $bankAccount */
        $bankAccount = $this->getOwnerRecord();

        return TransactionsTable::configure($table)
            ->headerActions([
                ImportCSVFileAction::make($bankAccount),
            ]);
    }
}
