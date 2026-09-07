<?php

namespace App\Filament\App\Resources\ContactPeople\Pages;

use App\Filament\App\Resources\ContactPeople\ContactPersonResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditContactPerson extends EditRecord
{
    protected static string $resource = ContactPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
