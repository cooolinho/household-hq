<?php

namespace App\Filament\Admin\Resources\Financial\BankAccounts\Pages;

use App\Filament\Admin\Resources\Financial\BankAccounts\BankAccountResource;
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
