<?php

namespace App\Filament\Admin\Resources\Inventory\Collections\Pages;

use App\Filament\Admin\Resources\Inventory\Collections\CollectionResource;
use App\Models\Inventory\Collection;
use Filament\Resources\Pages\CreateRecord;

class CreateCollection extends CreateRecord
{
    protected static string $resource = CollectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data[Collection::user_id] = auth()->id();

        return $data;
    }
}
