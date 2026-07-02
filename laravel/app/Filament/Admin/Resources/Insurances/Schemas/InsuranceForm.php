<?php

namespace App\Filament\Admin\Resources\Insurances\Schemas;

use App\Models\Enums\InsuranceTypeEnum;
use App\Models\Insurance;
use App\Util\CountriesUtil;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InsuranceForm
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
                        TextInput::make(Insurance::name)
                            ->required(),
                        TextInput::make(Insurance::company),
                        Select::make(Insurance::type)
                            ->options(InsuranceTypeEnum::options())
                            ->searchable()
                            ->placeholder('Select type'),
                        TextInput::make(Insurance::number),
                        DatePicker::make(Insurance::start_date),
                        DatePicker::make(Insurance::end_date),
                    ]),


                Section::make('Kontaktinformationen')
                    ->columnSpanFull()
                    ->heading('Kontaktinformationen')
                    ->description('Hier können Sie die Kontaktinformationen der Versicherung bearbeiten.')
                    ->schema([
                        TextInput::make(Insurance::contact_person),
                        TextInput::make(Insurance::phone),
                        TextInput::make(Insurance::email),
                    ]),


                // address columns
                Section::make('Adresse')
                    ->columnSpanFull()
                    ->heading('Adresse')
                    ->description('Hier können Sie die Adresse der Versicherung bearbeiten.')
                    ->columns(2)
                    ->schema([
                        TextInput::make(Insurance::address_line_1)
                            ->columnSpan(2),
                        TextInput::make(Insurance::address_line_2)
                            ->columnSpan(2),
                        TextInput::make(Insurance::address_zip)
                            ->columnSpan(1),
                        TextInput::make(Insurance::address_city)
                            ->columnSpan(1),
                        Select::make(Insurance::address_country)
                            ->options(CountriesUtil::options())
                    ]),
            ]);
    }
}
