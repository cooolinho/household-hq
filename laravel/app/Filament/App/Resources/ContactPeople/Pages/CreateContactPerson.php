<?php

namespace App\Filament\App\Resources\ContactPeople\Pages;

use App\Filament\App\Resources\ContactPeople\ContactPersonResource;
use App\Models\ContactPerson;
use Filament\Resources\Pages\CreateRecord;

class CreateContactPerson extends CreateRecord
{
    protected static string $resource = ContactPersonResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data[ContactPerson::user_id] = auth()->id();

        return $data;
    }
}
