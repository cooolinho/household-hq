<?php

namespace App\Filament\Admin\Resources\Financial\BankAccounts\Schemas;

use App\Models\Financial\BankAccount;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BankAccountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(BankAccount::name),
                TextEntry::make(BankAccount::account_holder),
                TextEntry::make(BankAccount::iban),
                TextEntry::make(BankAccount::bic),
                TextEntry::make(BankAccount::bank_name),
                TextEntry::make(BankAccount::balance),
                TextEntry::make(BankAccount::balance_date),
                TextEntry::make(BankAccount::type)
                    ->state(function (BankAccount $record) {
                        return $record->type->label();
                    }),
                TextEntry::make(BankAccount::created_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(BankAccount::updated_at)
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
