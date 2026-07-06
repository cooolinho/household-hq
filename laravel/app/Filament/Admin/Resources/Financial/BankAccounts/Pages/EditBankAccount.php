<?php

namespace App\Filament\Admin\Resources\Financial\BankAccounts\Pages;

use App\Filament\Admin\Resources\Financial\BankAccounts\BankAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBankAccount extends EditRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
