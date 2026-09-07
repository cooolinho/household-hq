<?php

namespace App\Filament\App\Resources\Documents\Pages;

use App\Filament\App\Resources\Documents\Schemas\ArticleDocumentForm;
use App\Models\Inventory\Article;

class CreateArticleDocument extends CreateRelatedDocument
{
    protected function getOwnerModelClass(): string
    {
        return Article::class;
    }

    protected function getDocumentFormClass(): string
    {
        return ArticleDocumentForm::class;
    }
}

