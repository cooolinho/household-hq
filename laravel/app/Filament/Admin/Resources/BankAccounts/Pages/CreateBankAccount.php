<?php

namespace App\Filament\Admin\Resources\BankAccounts\Pages;

use App\Filament\Admin\Resources\BankAccounts\BankAccountResource;
use App\Models\BankAccount;
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
