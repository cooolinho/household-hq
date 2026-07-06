<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Schemas;

use App\Models\Inventory\Article;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ArticleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(Article::name),
                TextEntry::make(Article::description)
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make(Article::amount)
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make(Article::serial_number)
                    ->placeholder('-'),
                TextEntry::make(Article::model_number)
                    ->placeholder('-'),
                TextEntry::make(Article::manufacturer)
                    ->placeholder('-'),
                TextEntry::make(Article::notes)
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make(Article::insured)
                    ->boolean()
                    ->placeholder('-'),
                IconEntry::make(Article::archived)
                    ->boolean()
                    ->placeholder('-'),
                TextEntry::make(Article::purchase_price)
                    ->money()
                    ->placeholder('-'),
                TextEntry::make(Article::purchase_place)
                    ->placeholder('-'),
                TextEntry::make(Article::purchase_date)
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(Article::warranty_lifetime)
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make(Article::warranty_until)
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(Article::warranty_details)
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make(Article::sale_price)
                    ->money()
                    ->placeholder('-'),
                TextEntry::make(Article::sold_to)
                    ->placeholder('-'),
                TextEntry::make(Article::sold_at)
                    ->date()
                    ->placeholder('-'),
                TextEntry::make(Article::belongs_to_parent . '.' . Article::name)
                    ->label('Parent')
                    ->placeholder('-'),
                TextEntry::make(Article::created_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(Article::updated_at)
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
