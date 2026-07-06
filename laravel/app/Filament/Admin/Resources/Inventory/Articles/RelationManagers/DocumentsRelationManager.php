<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\RelationManagers;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\Documents\Tables\DocumentsTable;
use App\Models\Inventory\Article;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    const string QUERY_PARAM_ARTICLE_ID = 'inventory_article_id';
    protected static string $relationship = Article::has_many_documents;

    protected static ?string $relatedResource = DocumentResource::class;

    public function table(Table $table): Table
    {
        return DocumentsTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->url(fn(): string => DocumentResource::getUrl('create', parameters: [
                        self::QUERY_PARAM_ARTICLE_ID => $this->ownerRecord->id,
                    ])),
            ]);
    }
}
