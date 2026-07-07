<?php

namespace App\Filament\Admin\Resources\Tags\Pages;

use App\Filament\Admin\Resources\Tags\TagResource;
use App\Models\Tag;
use Filament\Resources\Pages\CreateRecord;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data[Tag::user_id] = auth()->id();

        return $data;
    }
}
