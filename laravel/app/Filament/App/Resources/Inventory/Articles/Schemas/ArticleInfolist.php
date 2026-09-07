<?php

namespace App\Filament\App\Resources\Inventory\Articles\Schemas;

use App\Models\Inventory\Article;
use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ArticleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Basisdaten')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Article::name)
                            ->label('Name'),
                        TextEntry::make(Article::amount)
                            ->label('Anzahl')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make(Article::serial_number)
                            ->label('Seriennummer')
                            ->placeholder('-'),
                        TextEntry::make(Article::model_number)
                            ->label('Modellnummer')
                            ->placeholder('-'),
                        TextEntry::make(Article::manufacturer)
                            ->label('Hersteller')
                            ->placeholder('-'),
                        TextEntry::make(Article::description)
                            ->label('Beschreibung')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make(Article::notes)
                            ->label('Notizen')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Zuordnung')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Article::belongs_to_location . '.' . Location::belongs_to_collection . '.' . Collection::name)
                            ->label('Collection')
                            ->placeholder('-'),
                        TextEntry::make(Article::belongs_to_location . '.' . Location::name)
                            ->label('Standort')
                            ->placeholder('-'),
                        TextEntry::make(Article::belongs_to_parent . '.' . Article::name)
                            ->label('Übergeordneter Artikel')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Kauf & Garantie')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Article::purchase_price)
                            ->label('Kaufpreis')
                            ->money('EUR')
                            ->placeholder('-'),
                        TextEntry::make(Article::purchase_place)
                            ->label('Kaufort')
                            ->placeholder('-'),
                        TextEntry::make(Article::purchase_date)
                            ->label('Kaufdatum')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make(Article::warranty_lifetime)
                            ->label('Garantielaufzeit (Monate)')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make(Article::warranty_until)
                            ->label('Garantie bis')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make(Article::warranty_details)
                            ->label('Garantiedetails')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Verkauf')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Article::sale_price)
                            ->label('Verkaufspreis')
                            ->money('EUR')
                            ->placeholder('-'),
                        TextEntry::make(Article::sold_to)
                            ->label('Verkauft an')
                            ->placeholder('-'),
                        TextEntry::make(Article::sold_at)
                            ->label('Verkaufsdatum')
                            ->date()
                            ->placeholder('-'),
                    ]),

                Section::make('Status & Historie')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        IconEntry::make(Article::insured)
                            ->label('Versichert')
                            ->boolean()
                            ->placeholder('-'),
                        IconEntry::make(Article::archived)
                            ->label('Archiviert')
                            ->boolean()
                            ->placeholder('-'),
                        TextEntry::make(Article::created_at)
                            ->label('Erstellt am')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make(Article::updated_at)
                            ->label('Aktualisiert am')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
