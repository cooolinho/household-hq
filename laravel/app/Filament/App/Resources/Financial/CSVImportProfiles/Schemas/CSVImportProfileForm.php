<?php

namespace App\Filament\App\Resources\Financial\CSVImportProfiles\Schemas;

use App\Models\Financial\CSVImportProfile;
use App\Services\TransactionImport\AmountFormat;
use App\Services\TransactionImport\CsvEncoding;
use App\Services\TransactionImport\CsvFormat;
use App\Services\TransactionImport\TransactionImportField;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CSVImportProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        $emptyMapping = array_fill_keys(TransactionImportField::values(), '');

        return $schema
            ->components([
                Section::make('Profil')
                    ->columns(2)
                    ->schema([
                        TextInput::make(CSVImportProfile::name)
                            ->label('Name')
                            ->maxLength(255)
                            ->required(),

                        TextInput::make(CSVImportProfile::bank)
                            ->label('Bank')
                            ->maxLength(255),
                    ]),

                Section::make('CSV-Format')
                    ->columns(3)
                    ->schema([
                        Select::make(CSVImportProfile::delimiter)
                            ->label('Trennzeichen')
                            ->options(CsvFormat::delimiterOptions())
                            ->default(';')
                            ->required(),

                        Select::make(CSVImportProfile::encoding)
                            ->label('Zeichenkodierung')
                            ->options(CsvEncoding::options())
                            ->default(CsvEncoding::Auto->value)
                            ->required(),

                        Select::make(CSVImportProfile::amount_format)
                            ->label('Betragsformat')
                            ->options(AmountFormat::options())
                            ->default(AmountFormat::German->value)
                            ->helperText('Wählt das Zahlenformat der Beträge in der CSV-Datei.')
                            ->required(),

                        Toggle::make(CSVImportProfile::has_header)
                            ->label('Erste Zeile enthält Spaltennamen')
                            ->default(true)
                            ->inline(false),

                        TextInput::make(CSVImportProfile::offset_header)
                            ->label('Zeilen vor der Kopfzeile überspringen')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(255)
                            ->default(0)
                            ->required(),

                        TextInput::make(CSVImportProfile::date_format)
                            ->label('Datumsformat')
                            ->placeholder('automatisch')
                            ->helperText('Leer = automatisch, sonst PHP-Format wie d.m.Y.')
                            ->maxLength(32),

                        TextInput::make(CSVImportProfile::enclosure)
                            ->label('Textbegrenzer')
                            ->default('"')
                            ->maxLength(1)
                            ->required(),

                        TextInput::make(CSVImportProfile::escape)
                            ->label('Escape-Zeichen')
                            ->helperText('Leer = kein Escape-Zeichen.')
                            ->default('\\')
                            ->maxLength(1),
                    ]),

                Section::make('Spaltenzuordnung')
                    ->description('Wird am einfachsten im CSV-Import-Assistenten erstellt. Der Spaltenname hat Vorrang, die Spaltennummer (0 = erste Spalte) dient als Fallback und bei doppelten Spaltennamen.')
                    ->columns(2)
                    ->schema([
                        KeyValue::make(CSVImportProfile::header_mapping)
                            ->label('Spaltenname')
                            ->keyLabel('Transaktionsfeld')
                            ->valueLabel('CSV-Spaltenname')
                            ->addable(false)
                            ->deletable(false)
                            ->editableKeys(false)
                            ->default($emptyMapping)
                            ->afterStateHydrated(fn (KeyValue $component, ?array $state) => $component->state([...$emptyMapping, ...($state ?? [])])),

                        KeyValue::make(CSVImportProfile::mapping)
                            ->label('Spaltennummer')
                            ->keyLabel('Transaktionsfeld')
                            ->valueLabel('Spaltenindex (0-basiert)')
                            ->addable(false)
                            ->deletable(false)
                            ->editableKeys(false)
                            ->default($emptyMapping)
                            ->afterStateHydrated(fn (KeyValue $component, ?array $state) => $component->state([...$emptyMapping, ...($state ?? [])])),
                    ]),
            ]);
    }
}
