<?php

namespace App\Filament\App\Resources\Financial\BankAccounts\Pages;

use App\Filament\App\Resources\Financial\BankAccounts\BankAccountResource;
use App\Models\Financial\BankAccount;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data[BankAccount::user_id] = auth()->id();

        return $data;
    }
}
