<?php

namespace App\Filament\Admin\Resources\Financial\BankAccounts\Pages;

use App\Filament\Admin\Resources\Financial\BankAccounts\BankAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankAccounts extends ListRecords
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

//    public function table(Table $table): Table
//    {
//        $table->modifyQueryUsing(function ($query) {
//            $query->where(BankAccount::user_id, auth()->id());
//        });
//
//        return $table;
//    }
}
