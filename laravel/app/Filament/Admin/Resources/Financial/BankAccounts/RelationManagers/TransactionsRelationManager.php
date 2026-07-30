<?php

namespace App\Filament\Admin\Resources\Financial\BankAccounts\RelationManagers;

use App\Filament\Admin\Resources\Financial\Transactions\Actions\ImportCSVFileAction;
use App\Filament\Admin\Resources\Financial\Transactions\Schemas\TransactionForm;
use App\Filament\Admin\Resources\Financial\Transactions\Schemas\TransactionInfolist;
use App\Filament\Admin\Resources\Financial\Transactions\Tables\TransactionsTable;
use App\Jobs\FixedCostTransactionMatchingJob;
use App\Jobs\RecurringTransactionSuggestionDetectionJob;
use App\Models\Financial\BankAccount;
use App\Models\Financial\Transaction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
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
                            RecurringTransactionSuggestionDetectionJob::dispatchAfterResponse();

                            Notification::make()
                                ->title('Verknüpfung von Fixkosten-Transaktionen gestartet.')
                                ->success()
                                ->send();
                        }),

                    // delete all transactions with modal to set date range optionally
                    Action::make('delete_all')
                        ->label('Alle Transaktionen löschen')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Alle Transaktionen löschen')
                        ->modalDescription('Möchten Sie wirklich alle Transaktionen löschen? Sie können optional einen Datumsbereich angeben, um nur die Transaktionen in diesem Bereich zu löschen.')
                        ->modalSubmitActionLabel('Löschen')
                        ->schema([
                            DatePicker::make('start_date')
                                ->label('Startdatum (optional)')
                                ->placeholder('Startdatum auswählen'),
                            DatePicker::make('end_date')
                                ->label('Enddatum (optional)')
                                ->placeholder('Enddatum auswählen'),
                        ])
                        ->action(function (array $data) use ($bankAccount) {
                            $query = $bankAccount->transactions();

                            if (!empty($data['start_date'])) {
                                $query->whereDate(Transaction::date, '>=', $data['start_date']);
                            }

                            if (!empty($data['end_date'])) {
                                $query->whereDate(Transaction::date, '<=', $data['end_date']);
                            }

                            $count = $query->count();
                            $query->delete();

                            Notification::make()
                                ->title("{$count} Transaktionen gelöscht.")
                                ->success()
                                ->send();
                        }),
                ])->button()
            ]);
    }
}
