<?php

namespace App\Filament\App\Resources\Financial\BankAccounts\Pages;

use App\Filament\App\Resources\Financial\BankAccounts\BankAccountResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBankAccount extends ViewRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
