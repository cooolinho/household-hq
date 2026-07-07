<?php

namespace App\Filament\Admin\Resources\ContactPeople\Pages;

use App\Filament\Admin\Resources\ContactPeople\ContactPersonResource;
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
}
