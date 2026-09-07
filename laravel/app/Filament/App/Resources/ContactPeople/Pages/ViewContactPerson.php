<?php

namespace App\Filament\App\Resources\ContactPeople\Pages;

use App\Filament\App\Resources\ContactPeople\ContactPersonResource;
use App\Filament\App\Widgets\CommentsWidget;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactPerson extends ViewRecord
{
    protected static string $resource = ContactPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
