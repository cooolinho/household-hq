<?php

namespace App\Filament\App\Resources\ContactPeople\Pages;

use App\Filament\App\Resources\ContactPeople\ContactPersonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContactPeople extends ListRecords
{
    protected static string $resource = ContactPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
