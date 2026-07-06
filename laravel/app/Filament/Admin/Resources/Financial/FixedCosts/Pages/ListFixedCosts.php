<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Pages;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFixedCosts extends ListRecords
{
    protected static string $resource = FixedCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
