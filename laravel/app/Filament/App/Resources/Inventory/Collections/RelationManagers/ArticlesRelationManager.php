<?php

namespace App\Filament\App\Resources\Inventory\Collections\RelationManagers;

use App\Filament\App\Resources\Inventory\Articles\ArticleResource;
use App\Filament\App\Resources\Inventory\Articles\Tables\ArticlesTable;
use App\Models\Inventory\Article;
use App\Models\Inventory\Collection;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ArticlesRelationManager extends RelationManager
{
    protected static string $relationship = Collection::has_many_articles;

    public function table(Table $table): Table
    {
        return ArticlesTable::configure($table)
            ->headerActions([])
            ->recordActions([
                Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Article $record): string => ArticleResource::getUrl('view', ['record' => $record])),
                Action::make('edit')
                    ->label('Bearbeiten')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn(Article $record): string => ArticleResource::getUrl('edit', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }
}
