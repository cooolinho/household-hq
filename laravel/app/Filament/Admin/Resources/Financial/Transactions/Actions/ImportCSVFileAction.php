<?php

namespace App\Filament\Admin\Resources\Financial\Transactions\Actions;

use App\AppConfig;
use App\Exceptions\TransactionsImportException;
use App\Jobs\Scheduled\FixedCostTransactionMatchingJob;
use App\Jobs\Scheduled\RecurringTransactionSuggestionDetectionJob;
use App\Models\Financial\BankAccount;
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\Transaction;
use App\Services\TransactionsCSVReaderService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportCSVFileAction
{
    const string INPUT_FIELD_PROFILE = 'profile';
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
        $profiles = CSVImportProfile::query()
            ->orderBy(CSVImportProfile::name)
            ->pluck(CSVImportProfile::name, CSVImportProfile::id)
            ->toArray();

        return [
            Select::make(self::INPUT_FIELD_PROFILE)
                ->label('Import Profile')
                ->required()
                ->options($profiles),
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
            $profileID = $data[self::INPUT_FIELD_PROFILE];

            $profile = CSVImportProfile::query()->find($profileID);

            try {
                $csvFile = app(TransactionsCSVReaderService::class)
                    ->load($storage->path($file), $profile);
            } catch (TransactionsImportException $e) {
                Log::error('Error importing transactions: ' . $e->getMessage(), [
                    'bank_account_id' => $bankAccount->id,
                    'profile_id' => $profileID,
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

            FixedCostTransactionMatchingJob::dispatchAfterResponse();
            RecurringTransactionSuggestionDetectionJob::dispatchAfterResponse();
        };
    }
}
