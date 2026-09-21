<?php

namespace App\Filament\App\Resources\Financial\BankAccounts\Pages;

use App\Filament\App\Resources\Financial\BankAccounts\BankAccountResource;
use App\Filament\App\Resources\Financial\CSVImportProfiles\CSVImportProfileResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankAccounts extends ListRecords
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('csv_import')
                ->label(__('admin.resource.bank_account.action.csv_import'))
                ->url(CSVImportProfileResource::getUrl('index'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray'),
            CreateAction::make(),
        ];
    }
}
