<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Pages;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFixedCost extends ViewRecord
{
    protected static string $resource = FixedCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
