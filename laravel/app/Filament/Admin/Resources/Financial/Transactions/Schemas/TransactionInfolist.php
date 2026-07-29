<?php

namespace App\Filament\Admin\Resources\Financial\Transactions\Schemas;

use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Buchung')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Transaction::amount)
                            ->label('Betrag')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(fn(Transaction $record): string => ' ' . $record->amount_currency)
                            ->color(fn(Transaction $record) => $record->amount >= 0 ? 'success' : 'danger'),
                        TextEntry::make(Transaction::belongs_to_fixed_cost . '.' . FixedCost::name)
                            ->label('Verknüpfte Fixkosten')
                            ->badge()
                            ->color('success')
                            ->placeholder('Keine Verknüpfung'),
                        TextEntry::make(Transaction::date)
                            ->label('Buchungsdatum')
                            ->date(),
                        TextEntry::make(Transaction::value_date)
                            ->label('Wertstellung')
                            ->date(),
                    ]),

                Section::make('Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Transaction::payer)
                            ->label('Auftraggeber')
                            ->placeholder('-'),
                        TextEntry::make(Transaction::description)
                            ->label('Beschreibung')
                            ->placeholder('-'),
                        TextEntry::make(Transaction::purpose)
                            ->label('Verwendungszweck')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                Section::make('Kontostand & Historie')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make(Transaction::balance)
                            ->label('Kontostand')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(fn(Transaction $record): string => ' ' . $record->balance_currency),
                        TextEntry::make(Transaction::created_at)
                            ->label('Importiert am')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make(Transaction::updated_at)
                            ->label('Zuletzt aktualisiert')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
