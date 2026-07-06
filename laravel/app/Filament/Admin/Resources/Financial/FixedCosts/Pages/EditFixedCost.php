<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Pages;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFixedCost extends EditRecord
{
    protected static string $resource = FixedCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
