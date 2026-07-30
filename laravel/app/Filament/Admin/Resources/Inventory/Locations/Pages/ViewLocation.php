<?php

namespace App\Filament\Admin\Resources\Inventory\Locations\Pages;

use App\Filament\Admin\Resources\Inventory\Locations\LocationResource;
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
