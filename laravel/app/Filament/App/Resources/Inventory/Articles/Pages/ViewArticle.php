<?php

namespace App\Filament\App\Resources\Inventory\Articles\Pages;

use App\Filament\App\Resources\Documents\Actions\AssignExistingDocumentAction;
use App\Filament\App\Resources\Inventory\Articles\ArticleResource;
use App\Filament\App\Widgets\ArticleGalleryWidget;
use App\Filament\App\Widgets\CommentsWidget;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewArticle extends ViewRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AssignExistingDocumentAction::make(),
            EditAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ArticleGalleryWidget::class,
            CommentsWidget::class,
        ];
    }
}
