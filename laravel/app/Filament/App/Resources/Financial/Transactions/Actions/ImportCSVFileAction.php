<?php

namespace App\Filament\App\Resources\Financial\Transactions\Actions;

use App\AppConfig;
use App\Exceptions\TransactionsImportException;
use App\Jobs\Scheduled\CategorizeTransactionsJob;
use App\Jobs\Scheduled\FixedCostTransactionMatchingJob;
use App\Jobs\Scheduled\RecurringTransactionSuggestionDetectionJob;
use App\Jobs\Scheduled\RefreshTransactionStatisticsJob;
use App\Models\Financial\BankAccount;
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\Transaction;
use App\Services\TransactionsCSVReaderService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportCSVFileAction
{
    const string INPUT_FIELD_FILE = 'file';

    public static function make(BankAccount $bankAccount)
    {
        return Action::make('import')
            ->label('Import Transactions')
            ->schema(self::getSchema())
            ->action(self::getAction($bankAccount))
            ->requiresConfirmation()
            ->color('success')
            ->icon(Heroicon::OutlinedArrowDownTray);
    }

    /**
     * @return array
     */
    private static function getSchema(): array
    {
        return [
            FileUpload::make(self::INPUT_FIELD_FILE)
                ->label('CSV File')
                ->required()
                ->acceptedFileTypes(['text/csv', 'text/plain', '.csv'])
                ->maxSize(1024)
                ->disk(AppConfig::FILESYSTEM_TRANSACTION_IMPORT)
        ];
    }

    /**
     * @param BankAccount $bankAccount
     * @return Closure
     */
    private static function getAction(BankAccount $bankAccount): Closure
    {
        return function (array $data) use ($bankAccount) {
            $storage = Storage::disk(AppConfig::FILESYSTEM_TRANSACTION_IMPORT);
            $file = $data[self::INPUT_FIELD_FILE];

            $profile = $bankAccount->csvProfile ?? CSVImportProfile::query()->find($bankAccount->csv_profile_id);

            if (!$profile instanceof CSVImportProfile) {
                Log::error('Missing CSV import profile for bank account import', [
                    'bank_account_id' => $bankAccount->id,
                    'csv_profile_id' => $bankAccount->csv_profile_id,
                ]);
                Notification::make()
                    ->title('Error importing transactions.')
                    ->danger()
                    ->body('Kein gültiges Importprofil für dieses Bankkonto zugewiesen.')
                    ->send();

                return;
            }

            try {
                $csvFile = app(TransactionsCSVReaderService::class)
                    ->load($storage->path($file), $profile);
            } catch (TransactionsImportException $e) {
                Log::error('Error importing transactions: ' . $e->getMessage(), [
                    'bank_account_id' => $bankAccount->id,
                    'profile_id' => $profile->id,
                    'file' => $file,
                ]);
                Notification::make()
                    ->title('Error importing transactions.')
                    ->danger()
                    ->body($e->getMessage())
                    ->send();

                return;
            }

            $bankAccount
                ->transactions()
                ->upsert($csvFile->getRecords()->toArray(), Transaction::hash);

            Notification::make()
                ->title('Transactions imported successfully.')
                ->success()
                ->send();

            $bankAccount->updateBalance();

            // Kategorisierung muss vor dem Fixkosten-Matching laufen, damit die Kategorie-Komponente
            // des Matchings frisch importierte Transaktionen berücksichtigen kann. Ein Chain garantiert
            // die Reihenfolge, ein einfaches Dispatch hintereinander tut das unter Horizon/Redis nicht.
            Bus::chain([
                new CategorizeTransactionsJob(),
                new FixedCostTransactionMatchingJob(),
            ])->dispatch();

            RecurringTransactionSuggestionDetectionJob::dispatch();
            RefreshTransactionStatisticsJob::dispatch();
        };
    }
}
