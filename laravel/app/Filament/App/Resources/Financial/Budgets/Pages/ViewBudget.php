<?php

namespace App\Filament\App\Resources\Financial\Budgets\Pages;

use App\Filament\App\Resources\Financial\Budgets\BudgetResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBudget extends ViewRecord
{
    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
