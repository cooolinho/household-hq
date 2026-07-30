<?php

namespace App\Filament\Admin\Resources\Financial\BankAccounts\RelationManagers;

use App\Filament\Admin\Resources\Financial\Transactions\Actions\ImportCSVFileAction;
use App\Filament\Admin\Resources\Financial\Transactions\Schemas\TransactionForm;
use App\Filament\Admin\Resources\Financial\Transactions\Schemas\TransactionInfolist;
use App\Filament\Admin\Resources\Financial\Transactions\Tables\TransactionsTable;
use App\Jobs\FixedCostTransactionMatchingJob;
use App\Models\Financial\BankAccount;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
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
                ActionGroup::make([
                    ImportCSVFileAction::make($bankAccount),

                    Action::make('match')
                        ->label('Verknüpfung von Fixkosten-Transaktionen starten')
                        ->icon('heroicon-o-link')
                        ->action(function () use ($bankAccount) {
                            FixedCostTransactionMatchingJob::dispatchAfterResponse();

                            Notification::make()
                                ->title('Verknüpfung von Fixkosten-Transaktionen gestartet.')
                                ->success()
                                ->send();
                        })
                ])->button()
            ]);
    }
}
