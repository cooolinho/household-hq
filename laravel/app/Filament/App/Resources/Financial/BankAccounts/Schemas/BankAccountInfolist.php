<?php

namespace App\Filament\App\Resources\Financial\BankAccounts\Schemas;

use App\Models\Financial\BankAccount;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class BankAccountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('bank_account_details')
                    ->hiddenLabel(true)
                    ->html()
                    ->columnSpanFull()
                    ->state(fn(BankAccount $record): HtmlString => new HtmlString(
                        view('filament.app.resources.financial.bank-accounts.infolists.bank-account-details', [
                            'record' => $record,
                        ])->render()
                    )),
            ]);
    }
}
