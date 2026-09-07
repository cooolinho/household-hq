<?php

namespace App\Filament\App\Resources\Inventory\Collections\Pages;

use App\Filament\App\Resources\Inventory\Collections\CollectionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCollection extends ViewRecord
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
