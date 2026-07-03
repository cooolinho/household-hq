<?php

namespace App\Filament\Admin\Resources\CSVImportProfiles\Schemas;

use App\Models\CSVImportProfile;
use App\Models\Transaction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CSVImportProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make(CSVImportProfile::name)
                    ->required(),

                TextInput::make(CSVImportProfile::bank),

                Select::make(CSVImportProfile::delimiter)
                    ->options([
                        ';' => ';',
                        ',' => ',',
                        "\t" => 'TAB',
                    ])
                    ->required(),

                TextInput::make(CSVImportProfile::enclosure)
                    ->default('"')
                    ->required(),

                TextInput::make(CSVImportProfile::escape)
                    ->default('\\')
                    ->required(),

                TextInput::make(CSVImportProfile::offset_header)
                    ->numeric()
                    ->default(0)
                    ->required(),

                KeyValue::make(CSVImportProfile::mapping)
                    ->keyLabel('Transaction Column')
                    ->valueLabel('CSV Index')
                    ->addable(false)
                    ->editableKeys(false)
                    ->default([
                        Transaction::date => '',
                        Transaction::value_date => '',
                        Transaction::payer => '',
                        Transaction::description => '',
                        Transaction::purpose => '',
                        Transaction::balance => '',
                        Transaction::balance_currency => '',
                        Transaction::amount => '',
                        Transaction::amount_currency => '',
                    ])
            ]);
    }
}
