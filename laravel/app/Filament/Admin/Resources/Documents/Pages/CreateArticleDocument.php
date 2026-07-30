<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Filament\Admin\Resources\Documents\Schemas\ArticleDocumentForm;
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

