<?php

namespace App\Filament\Admin\Resources\Inventory\Locations\RelationManagers;

use App\Filament\Admin\Resources\Inventory\Articles\Schemas\ArticleForm;
use App\Filament\Admin\Resources\Inventory\Articles\Schemas\ArticleInfolist;
use App\Filament\Admin\Resources\Inventory\Articles\Tables\ArticlesTable;
use App\Models\Inventory\Location;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ArticlesRelationManager extends RelationManager
{
    protected static string $relationship = Location::has_many_articles;

    public function form(Schema $schema): Schema
    {
        return ArticleForm::configure($schema, $this->ownerRecord->id);
    }

    public function infolist(Schema $schema): Schema
    {
        return ArticleInfolist::configure($schema);
    }

    public function table(Table $table): Table
    {
        return ArticlesTable::configure($table)
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
