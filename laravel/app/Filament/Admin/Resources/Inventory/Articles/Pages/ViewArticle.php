<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\Pages;

use App\Filament\Admin\Resources\Documents\Actions\AssignExistingDocumentAction;
use App\Filament\Admin\Resources\Inventory\Articles\ArticleResource;
use App\Filament\Admin\Widgets\CommentsWidget;
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
            CommentsWidget::class,
        ];
    }
}
