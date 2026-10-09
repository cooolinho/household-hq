<?php

namespace App\Filament\App\Resources\Financial\Transactions\Actions;

use App\Filament\App\Pages\TransactionCsvImportPage;
use App\Models\Financial\BankAccount;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * Öffnet den CSV-Import-Assistenten für ein Bankkonto (inkl. dessen Import-Profil).
 */
class ImportCSVFileAction
{
    public static function make(BankAccount $bankAccount): Action
    {
        return Action::make('import')
            ->label('Transaktionen importieren (CSV)')
            ->url(TransactionCsvImportPage::getUrl(['bankAccount' => $bankAccount->id]))
            ->color('success')
            ->icon(Heroicon::OutlinedArrowDownTray);
    }
}
