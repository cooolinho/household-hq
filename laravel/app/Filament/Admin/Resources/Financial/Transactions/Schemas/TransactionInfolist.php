<?php

namespace App\Filament\Admin\Resources\Financial\Transactions\Schemas;

use App\Models\Financial\Transaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                    ]),

                TextEntry::make('statement')
                    ->label('Transaktionsdetails')
                    ->hiddenLabel(true)
                    ->html()
                    ->columnSpanFull()
                    ->state(fn(Transaction $record): HtmlString => new HtmlString(
                        view('filament.admin.resources.financial.transactions.actions.transaction-statement', [
                            'record' => $record,
                        ])->render()
                    )),

                Section::make('Kontostand & Historie')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsed()
                    ->schema([
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
