<?php

namespace App\Filament\App\Resources\Inventory\Locations\Pages;

use App\Filament\App\Resources\Inventory\Locations\LocationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLocation extends ViewRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
