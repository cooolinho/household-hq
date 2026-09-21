<?php

namespace App\Filament\App\Resources\Financial\BankAccounts\Schemas;

use App\Filament\App\Resources\Tags\TagResource;
use App\Models\Enums\BankAccountTypeEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\CSVImportProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Basisinformationen')
                    ->columnSpanFull()
                    ->heading('Basisinformationen')
                    ->description('Hier können Sie die Basisinformationen der Versicherung bearbeiten.')
                    ->schema([
                        TextInput::make(BankAccount::name)
                            ->required(),
                        Select::make(BankAccount::type)
                            ->options(BankAccountTypeEnum::options())
                            ->searchable()
                            ->placeholder('Select type')
                            ->required(),
                        Select::make(BankAccount::csv_profile_id)
                            ->label('CSV-Profil')
                            ->relationship(
                                name: BankAccount::belongs_to_csv_profile,
                                titleAttribute: CSVImportProfile::name,
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Section::make('Bankinformationen')
                    ->columnSpanFull()
                    ->heading('Bankinformationen')
                    ->description('Hier können Sie die Bankinformationen der Versicherung bearbeiten.')
                    ->schema([
                        TextInput::make(BankAccount::account_holder),
                        TextInput::make(BankAccount::iban),
                        TextInput::make(BankAccount::bic),
                        TextInput::make(BankAccount::bank_name),
                    ]),

                TagResource::getMorphToManySelect($schema, BankAccount::morph_to_many_tags)
            ]);
    }
}
