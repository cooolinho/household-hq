<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Schemas;

use App\Filament\Admin\Resources\Tags\TagResource;
use App\Models\Inventory\Article;
use App\Models\Inventory\ArticleImage;
use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ArticleForm
{
    public static function configure(Schema $schema, ?int $locationId = null): Schema
    {
        return $schema
            ->components([
                ...static::components($schema, $locationId),
            ]);
    }

    public static function components(Schema $schema, ?int $locationId = null): array
    {
        return [
            Section::make('Basisdaten')
                ->columns(2)
                ->schema([
                    TextInput::make(Article::name)
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make(Article::amount)
                        ->label('Anzahl')
                        ->numeric()
                        ->minValue(1),
                    TextInput::make(Article::serial_number)
                        ->label('Seriennummer'),
                    TextInput::make(Article::model_number)
                        ->label('Modellnummer'),
                    TextInput::make(Article::manufacturer)
                        ->label('Hersteller'),
                    Textarea::make(Article::description)
                        ->label('Beschreibung')
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make(Article::notes)
                        ->label('Notizen')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Zuordnung')
                ->columns(2)
                ->schema([
                    Select::make(Article::location_id)
                        ->label('Standort')
                        ->default($locationId)
                        ->required()
                        ->relationship(
                            Article::belongs_to_location,
                            Location::name,
                            modifyQueryUsing: fn(Builder $query) => $query->whereHas(
                                Location::belongs_to_collection,
                                fn(Builder $collectionQuery) => $collectionQuery->where(Collection::user_id, auth()->id())
                            )
                        )
                        ->disabled($locationId !== null)
                        ->dehydrated(),
                    Select::make(Article::parent_id)
                        ->label('Übergeordneter Artikel')
                        ->relationship(
                            Article::belongs_to_parent,
                            Article::name,
                            modifyQueryUsing: fn(Builder $query) => $query->whereHas(
                                Article::belongs_to_location . '.' . Location::belongs_to_collection,
                                fn(Builder $collectionQuery) => $collectionQuery->where(Collection::user_id, auth()->id())
                            )
                        )
                        ->searchable()
                        ->preload(),
                    TagResource::getMorphToManySelect($schema, Article::morph_to_many_tags)
                        ->columnSpanFull(),
                ]),

            Section::make('Kauf & Garantie')
                ->columns(2)
                ->schema([
                    TextInput::make(Article::purchase_price)
                        ->label('Kaufpreis')
                        ->numeric()
                        ->prefix('€'),
                    TextInput::make(Article::purchase_place)
                        ->label('Kaufort'),
                    DatePicker::make(Article::purchase_date)
                        ->label('Kaufdatum'),
                    TextInput::make(Article::warranty_lifetime)
                        ->label('Garantielaufzeit (Monate)')
                        ->numeric()
                        ->minValue(0),
                    DatePicker::make(Article::warranty_until)
                        ->label('Garantie bis'),
                    Textarea::make(Article::warranty_details)
                        ->label('Garantiedetails')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Verkauf')
                ->columns(2)
                ->schema([
                    TextInput::make(Article::sale_price)
                        ->label('Verkaufspreis')
                        ->numeric()
                        ->prefix('€'),
                    TextInput::make(Article::sold_to)
                        ->label('Verkauft an'),
                    DatePicker::make(Article::sold_at)
                        ->label('Verkaufsdatum'),
                ]),

            Section::make('Status')
                ->columns(2)
                ->schema([
                    Toggle::make(Article::insured)
                        ->label('Versichert'),
                    Toggle::make(Article::archived)
                        ->label('Archiviert'),
                ]),

            Section::make('Vorschaubilder')
                ->description('Mehrere Bilder für die Artikel-Vorschau hochladen.')
                ->schema([
                    Repeater::make(Article::has_many_preview_images)
                        ->relationship(Article::has_many_preview_images)
                        ->reorderable()
                        ->defaultItems(0)
                        ->schema([
                            FileUpload::make(ArticleImage::path)
                                ->label('Bild')
                                ->disk(ArticleImage::STORAGE_DISK)
                                ->image()
                                ->required(),
                            TextInput::make(ArticleImage::sort)
                                ->label('Sortierung')
                                ->numeric()
                                ->default(0)
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
