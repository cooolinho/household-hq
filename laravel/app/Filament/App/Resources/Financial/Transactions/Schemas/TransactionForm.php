<?php

namespace App\Filament\App\Resources\Financial\Transactions\Schemas;

use App\Filament\App\Resources\Tags\TagResource;
use App\Models\Financial\Transaction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make(Transaction::date)
                    ->required(),
                DatePicker::make(Transaction::value_date)
                    ->required(),
                TextInput::make(Transaction::payer)
                    ->required(),
                TextInput::make(Transaction::description)
                    ->required(),
                TextInput::make(Transaction::purpose)
                    ->required(),
                TextInput::make(Transaction::balance)
                    ->required()
                    ->numeric(),
                TextInput::make(Transaction::balance_currency)
                    ->required(),
                TextInput::make(Transaction::amount)
                    ->required()
                    ->numeric(),
                TextInput::make(Transaction::amount_currency)
                    ->required(),

                TagResource::getMorphToManySelect($schema, Transaction::morph_to_many_tags)
            ]);
    }
}
