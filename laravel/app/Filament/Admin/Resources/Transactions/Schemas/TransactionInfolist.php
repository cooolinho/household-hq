<?php

namespace App\Filament\Admin\Resources\Transactions\Schemas;

use App\Models\Transaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(Transaction::date)
                    ->date(),
                TextEntry::make(Transaction::value_date)
                    ->date(),
                TextEntry::make(Transaction::payer),
                TextEntry::make(Transaction::description),
                TextEntry::make(Transaction::purpose),
                TextEntry::make(Transaction::balance)
                    ->numeric(),
                TextEntry::make(Transaction::balance_currency),
                TextEntry::make(Transaction::amount)
                    ->numeric(),
                TextEntry::make(Transaction::amount_currency),
                TextEntry::make(Transaction::created_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(Transaction::updated_at)
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
