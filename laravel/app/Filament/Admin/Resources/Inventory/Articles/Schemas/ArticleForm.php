<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Schemas;

use App\Filament\Admin\Resources\Tags\TagResource;
use App\Models\Inventory\Article;
use App\Models\Inventory\Location;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema, ?int $locationId = null): Schema
    {
        return $schema
            ->components([
                TextInput::make(Article::name)
                    ->required(),
                Textarea::make(Article::description)
                    ->columnSpanFull(),
                TextInput::make(Article::amount)
                    ->numeric(),
                TextInput::make(Article::serial_number),
                TextInput::make(Article::model_number),
                TextInput::make(Article::manufacturer),
                Textarea::make(Article::notes)
                    ->columnSpanFull(),
                Toggle::make(Article::insured),
                Toggle::make(Article::archived),
                TextInput::make(Article::purchase_price)
                    ->numeric()
                    ->prefix('$'),
                TextInput::make(Article::purchase_place),
                DatePicker::make(Article::purchase_date),
                TextInput::make(Article::warranty_lifetime)
                    ->numeric(),
                DatePicker::make(Article::warranty_until),
                Textarea::make(Article::warranty_details)
                    ->columnSpanFull(),
                TextInput::make(Article::sale_price)
                    ->numeric()
                    ->prefix('$'),
                TextInput::make(Article::sold_to),
                DatePicker::make(Article::sold_at),
                Select::make(Article::parent_id)
                    ->relationship(Article::belongs_to_parent, Article::name),
                Select::make(Article::location_id)
                    ->default($locationId)
                    ->relationship(Article::belongs_to_location, Location::name),

                TagResource::getMorphToManySelect($schema, Article::morph_to_many_tags)
            ]);
    }
}
